#! /usr/bin/env python

import json
import time
import random
import socket
import SocketServer
import socketpool
import nntp

class NNTPReaderConnector(socketpool.Connector, nntp.NNTPClient):

    def __init__(self, host, port, backend_mod, pool=None, username="anonymous", password="anonymous", timeout=30, use_ssl=False):
        if backend_mod.Socket != socket.socket:
            raise ValueError("Bad backend")
        nntp.NNTPClient.__init__(self, host, port, username, password, timeout=timeout, use_ssl=use_ssl)
        self.host = host
        self.port = port
        self.backend_mod = backend_mod
        self._connected = True
        self._life = time.time() - random.randint(0, 10)
        self._pool = pool

    def __del__(self):
        self.release()

    def matches(self, **match_options):
        target_host = match_options.get('host')
        target_port = match_options.get('port')
        return target_host == self.host and target_port == self.port

    def is_connected(self):
        if self._connected:
            return socketpool.util.is_connected(self.socket)
        return False

    def handle_exception(self, exception):
        print('got an exception')
        print(str(exception))

    def get_lifetime(self):
        return self._life

    def invalidate(self):
        self.close()
        self._connected = False
        self._life = -1

    def release(self):
        if self._pool is not None:
            if self._connected:
                self._pool.release_connection(self)
            else:
                self._pool = None


# NNTP proxy request handler for nZEDb
class NNTPProxyRequestHandler(SocketServer.StreamRequestHandler):

    def handle(self):
        with self.server.nntp_reader_pool.connection() as nntp_reader:
            self.wfile.write("200 localhost NNRP Service Ready.\r\n")
            while True:
                data = self.rfile.readline()
                if len(data) == 0:
                    break;
                data = data.strip()
                print(data)
                if data.startswith("AUTHINFO user") or data.startswith("AUTHINFO pass"):
                    self.wfile.write("281 Ok\r\n")
                elif data.startswith("XFEATURE"):
                    self.wfile.write("290 feature enabled\r\n")
                elif data.startswith("GROUP"):
                    total, first, last, group = nntp_reader.group(data.split(None, 1)[1])
                    self.wfile.write("211 %d %d %d %s\r\n" % (total, first, last, group))
                elif data.startswith("XOVER"):
                    rng = data.split(None, 1)[1]
                    rng = tuple(map(int, rng.split("-")))
                    xover_gen = nntp_reader.xover_gen(rng)
                    self.wfile.write("224 data follows\r\n")
                    for entry in xover_gen:
                        self.wfile.write("\t".join(entry) + "\r\n")
                    self.wfile.write(".\r\n")
                elif data.startswith("HEAD"):
                    msgid = data.split(None, 1)[1]
                    head = nntp_reader.head(msgid)
                    self.wfile.write("221 %s\r\n" % (msgid))
                    head = "\r\n".join([": ".join(item) for item in head.items()]) + "\r\n\r\n"
                    self.wfile.write(head)
                    self.wfile.write(".\r\n")
                elif data.startswith("BODY"):
                    msgid = data.split(None, 1)[1]
                    body = nntp_reader.body(msgid)
                    self.wfile.write("222 %s\r\n" % (msgid))
                    self.wfile.write(body)
                    self.wfile.write(".\r\n")
                elif data.startswith("LIST OVERVIEW.FMT"):
                    fmt = nntp_reader.list_overview_fmt()
                    self.wfile.write("215 Order of fields in overview database.\r\n")
                    fmt = "\r\n".join(["%s:%s" % (f[0], "full" if f[1] else "") for f in fmt]) + "\r\n" 
                    self.wfile.write(fmt)
                    self.wfile.write(".\r\n")
                elif data.startswith("QUIT"):
                    break
                else:
                    self.wfile.write("500 What?\r\n")

# NNTP proxy server for nZEDb
class NNTPProxyServer(SocketServer.ThreadingTCPServer):

    allow_reuse_address = True

    def __init__(self, server_address, RequestHandlerClass, nntp_reader_pool, bind_and_activate=True):
        SocketServer.ThreadingTCPServer.__init__(self, server_address, RequestHandlerClass, bind_and_activate=bind_and_activate)
        self.nntp_reader_pool = nntp_reader_pool

if __name__ == "__main__":

    with open("nntpproxy.conf", "rb") as fd:
        config = json.load(fd)

    nntp_reader_pool = socketpool.ConnectionPool(
        NNTPReaderConnector,
        max_lifetime=30000,
        max_size=config["pool"]["size"],
        options=config["usenet"]
    )

    addr = (config["proxy"]["host"], config["proxy"]["port"])
    proxy = NNTPProxyServer(addr, NNTPProxyRequestHandler, nntp_reader_pool)

    print "NNTPProxy listening on %s:%d" % addr
    proxy.serve_forever()
