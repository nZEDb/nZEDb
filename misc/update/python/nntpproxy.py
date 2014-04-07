#!/usr/bin/env python
# -*- coding: utf-8 -*-

import json
import time
import random
import socket
import SocketServer
import socketpool
import nntp
from lib.info import bcolors

class NNTPClientConnector(socketpool.Connector, nntp.NNTPClient):

	def __init__(self, host, port, backend_mod, pool=None, username="anonymous", password="anonymous", timeout=60, use_ssl=False):
		self.host = host
		self.port = port
		self.backend_mod = backend_mod
		self._pool = pool
		self._connected = False
		self._life = time.time() - random.randint(0, 10)
		if backend_mod.Socket != socket.socket:
			raise ValueError("Bad backend")
		nntp.NNTPClient.__init__(self, self.host, self.port, username, password, timeout=timeout, use_ssl=use_ssl)
		self.id = self.socket.getsockname()[1]
		print(bcolors.HEADER + "%5d NEW CONNECTION" % self.id + bcolors.ENDC)
		self._connected = True
		self.xfeature_compress_gzip()

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
		print(bcolors.ERROR + str(exception) + bcolors.ENDC)
		self.release()
		self.invalidate()

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
		with self.server.nntp_client_pool.connection() as nntp_client:
			self.wfile.write("200 localhost NNRP Service Ready.\r\n")
			for line in self.rfile:
				data = line.strip()
				if data.startswith("GROUP"):
					print(bcolors.ALTERNATE + "%5d %s" % (nntp_client.id, data))
				else:
					print(bcolors.HEADER + "%5d " % (nntp_client.id)) + (bcolors.PRIMARY + "%s" % (data))
				if data.startswith("AUTHINFO user") or data.startswith("AUTHINFO pass"):
					self.wfile.write("281 Ok\r\n")
				elif data.startswith("XFEATURE"):
					self.wfile.write("290 feature enabled\r\n")
				elif data.startswith("GROUP"):
					total, first, last, group = nntp_client.group(data.split(None, 1)[1])
					self.wfile.write("211 %d %d %d %s\r\n" % (total, first, last, group))
				elif data.startswith("XOVER"):
					rng = data.split(None, 1)[1]
					rng = tuple(map(int, rng.split("-")))
					xover_gen = nntp_client.xover_gen(rng)
					self.wfile.write("224 data follows\r\n")
					for entry in xover_gen:
						self.wfile.write("\t".join(entry) + "\r\n")
					self.wfile.write(".\r\n")
				elif data.startswith("ARTICLE"):
					msgid = data.split(None, 1)[1]
					article = nntp_client.article(msgid, False)
					# check no of return values for compatibility with pynntp<=0.8.3
					if len(article) == 2:
						articleno, head, body = 0, article[0], article[1]
					else:
						articleno, head, body = article
					self.wfile.write("220 %d %s\r\n" % (articleno, msgid))
					head = "\r\n".join([": ".join(item) for item in head.items()]) + "\r\n\r\n"
					self.wfile.write(head)
					self.wfile.write(body.replace("\r\n.", "\r\n.."))
					self.wfile.write(".\r\n")
				elif data.startswith("HEAD"):
					msgid = data.split(None, 1)[1]
					head = nntp_client.head(msgid)
					self.wfile.write("221 %s\r\n" % (msgid))
					head = "\r\n".join([": ".join(item) for item in head.items()]) + "\r\n\r\n"
					self.wfile.write(head)
					self.wfile.write(".\r\n")
				elif data.startswith("BODY"):
					msgid = data.split(None, 1)[1]
					try:
						body = nntp_client.body(msgid)
						self.wfile.write("222 %s\r\n" % (msgid))
						self.wfile.write(body.replace("\r\n.", "\r\n.."))
						self.wfile.write(".\r\n")
					except Exception as e:
						print(bcolors.ERROR + str(e) + bcolors.ENDC)
						self.wfile.write("430 No Such Article\r\n")
				elif data.startswith("LIST OVERVIEW.FMT"):
					fmt = nntp_client.list_overview_fmt()
					self.wfile.write("215 Order of fields in overview database.\r\n")
					fmt = "\r\n".join(["%s:%s" % (f[0], "full" if f[1] else "") for f in fmt]) + "\r\n"
					self.wfile.write(fmt)
					self.wfile.write(".\r\n")
				elif data == "LIST":
					list_gen = nntp_client.list_gen()
					self.wfile.write("215 list of newsgroups follows\r\n")
					for entry in list_gen:
						self.wfile.write("%s %d %d %s\r\n" % entry)
					self.wfile.write(".\r\n")
				elif data.startswith("LIST ACTIVE") and not data.startswith("LIST ACTIVE.TIMES"):
					pattern = data[11:].strip() or None
					active_gen = nntp_client.list_active_gen(pattern)
					self.wfile.write("215 list of newsgroups follows\r\n")
					for entry in active_gen:
						self.wfile.write("%s %d %d %s\r\n" % entry)
					self.wfile.write(".\r\n")
					self.wfile.write(str(e) + "\r\n")
				elif data.startswith("QUIT"):
					self.wfile.write("205 Connection closing\r\n")
					break
				else:
					self.wfile.write("500 What?\r\n")

# NNTP proxy server for nZEDb
class NNTPProxyServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):

	allow_reuse_address = True

	def __init__(self, server_address, RequestHandlerClass, nntp_client_pool, bind_and_activate=True):
		SocketServer.TCPServer.__init__(self, server_address, RequestHandlerClass, bind_and_activate=bind_and_activate)
		self.nntp_client_pool = nntp_client_pool

if __name__ == "__main__":

	import sys
	try:
		if len(sys.argv) == 1:
			import os
			pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
			with open(pathname+"/lib/nntpproxy.conf", "rb") as fd:
				config = json.load(fd)
		else:
			with open(sys.argv[1], "rb") as fd:
				config = json.load(fd)
	except IndexError:
		sys.stderr.write("Usage: %s configfile\n" % sys.argv[0])
		sys.exit(1)
	except IOError as e:
		sys.stderr.write("Failed to open config file (%s)\n" % e)
		sys.exit(1)
	except ValueError as e:
		sys.stderr.write("Failed to parse config file (%s)\n" % e)
		sys.exit(1)

	nntp_client_pool = socketpool.ConnectionPool(
		NNTPClientConnector,
		max_lifetime=30000,
		max_size=config["pool"]["size"],
		options=config["usenet"]
	)

	addr = (config["proxy"]["host"], config["proxy"]["port"])
	proxy = NNTPProxyServer(addr, NNTPProxyRequestHandler, nntp_client_pool)
	remote = (config["usenet"]["host"], config["usenet"]["port"])
	print(bcolors.HEADER + "NNTPProxy listening on %s:%d" % addr)
	print(bcolors.HEADER + "NNTPProxy connected to %s:%d" % remote)
	proxy.serve_forever()
