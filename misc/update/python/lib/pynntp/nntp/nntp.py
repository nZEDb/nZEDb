#!/usr/bin/python
"""
An NNTP library - a bit more useful than the nntplib one (hopefully).
Copyright (C) 2013  Byron Platt

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
"""

import ssl
import zlib
import socket
import datetime
import cStringIO
import utils
import iodict
import fifo
import yenc
import date


class NNTPError(Exception):
    """Base class for all NNTP errors.
    """
    pass

class NNTPSyncError(NNTPError):
    """NNTP sync errors.

    Generally raised when a command is issued while another command it still
    active.
    """
    pass

class NNTPReplyError(NNTPError):
    """NNTP response status errors.
    """
    def __init__(self, code, message):
        NNTPError.__init__(self, code, message)

    def code(self):
        """The response status code.
        """
        return self.args[0]

    def message(self):
        """The response message.
        """
        return self.args[1]

    def __str__(self):
        return "%d %s" % self.args

class NNTPTemporaryError(NNTPReplyError):
    """NNTP temporary errors.

    Temporary errors have response codes from 400 to 499.
    """
    pass

class NNTPPermanentError(NNTPReplyError):
    """NNTP permanent errors.

    Permanent errors have response codes from 500 to 599.
    """
    pass

# TODO: Add the status line as a parameter ?
class NNTPProtocolError(NNTPError):
    """NNTP protocol error.

    Protcol errors are raised when the response status is invalid.
    """
    pass

class NNTPDataError(NNTPError):
    """NNTP data error.

    Data errors are raised when the content of a response cannot be parsed.
    """
    pass


class BaseNNTPClient(object):
    """NNTP BaseNNTPClient.

    Base class for NNTP clients implements the basic command interface and
    transparently handles compressed replies.
    """
    def __init__(self, host, port=119, username="", password="", timeout=30, use_ssl=False):
        """Constructor for BasicNNTPClient.

        Connects to usenet server and enters reader mode.

        Args:
            host: Hostname for usenet server.
            port: Port for usenet server.
            username: Username for usenet account (default "anonymous")
            password: Password for usenet account (default "anonymous")
            timeout: Connection timeout (default 30 seconds)
            use_ssl: Should we use ssl (default False)

        Raises:
            IOError (socket.error): On error in underlying socket and/or ssl
                wrapper. See socket and ssl modules for further details.
            NNTPReplyError: On bad response code from server.
        """
        self.socket = socket.socket()
        if use_ssl:
            self.socket = ssl.wrap_socket(self.socket)
        self.socket.settimeout(timeout)

        self.__buffer = fifo.Fifo()
        self.__generating = False

        self.username = username
        self.password = password

        # connect
        self.socket.connect((host, port))
        code, message = self.status()
        if not code in [200, 201]:
            raise NNTPReplyError(code, message)

    def __recv(self, size=4096):
        """Reads data from the socket.

        Raises:
            NNTPError: When connection times out or read from socket fails.
        """
        data = self.socket.recv(size)
        if not data:
            raise NNTPError("Failed to read from socket")
        self.__buffer.write(data)

    def __line_gen(self):
        """Generator that reads a line of data from the server.

        It first attempts to read from the internal buffer. If there is not
        enough data to read a line it then requests more data from the server
        and adds it to the buffer. This process repeats until a line of data
        can be read from the internal buffer.

        Yields:
            A line of data when it becomes available.
        """
        while True:
            line = self.__buffer.readline()
            if not line:
                self.__recv()
                continue
            yield line

    def __buf_gen(self, length=0):
        """Generator that reads a block of data from the server.

        It first attempts to read from the internal buffer. If there is not
        enough data in the internal buffer it then requests more data from the
        server and adds it to the buffer.

        Args:
            length: An optional amount of data to retrieve. A length of 0 (the
                default) will retrieve a least one buffer of data.

        Yields:
            A block of data when enough data becomes available.

        Note:
            If a length of 0 is supplied then the size of the yielded buffer can
            vary. If there is data in the internal buffer it will yield all of
            that data otherwise it will yield the the data returned by a recv
            on the socket.
        """
        while True:
            buf = self.__buffer.read(length)
            if not buf:
                self.__recv()
                continue
            yield buf

    def status(self):
        """Reads a command response status.

        If there is no response message then the returned status message will
        be an empty string.

        Raises:
            NNTPError: If data is required to be read from the socket and fails.
            NNTPProtocolError: If the status line can't be parsed.
            NNTPTemporaryError: For status code 400-499
            NNTPPermanentError: For status code 500-599

        Returns:
            A tuple of status code (as an integer) and status message.
        """
        line = next(self.__line_gen()).rstrip()
        parts = line.split(None, 1)

        try:
            code, message = int(parts[0]), ""
        except ValueError:
            raise NNTPProtocolError(line)

        if code < 100 or code >= 600:
            raise NNTPProtocolError(line)

        if len(parts) > 1:
            message = parts[1]

        if 400 <= code <= 499:
            raise NNTPTemporaryError(code, message)

        if 500 <= code <= 599:
            raise NNTPPermanentError(code, message)

        return code, message

    def __info_plain_gen(self):
        """Generator for the lines of an info (textual) response.

        When a terminating line (line containing single period) is received the
        generator exits.

        If there is a line begining with an 'escaped' period then the extra
        period is trimmed.

        Yields:
            A line of the info response.

        Raises:
            NNTPError: If data is required to be read from the socket and fails.
        """
        self.__generating = True

        for line in self.__line_gen():
            if line == ".\r\n":
                break
            if line.startswith("."):
                yield line[1:]
            yield line

        self.__generating = False

    def __info_gzip_gen(self):
        """Generator for the lines of a compressed info (textual) response.

        Compressed responses are an extension to the NNTP protocol supported by
        some usenet servers to reduce the bandwidth of heavily used range style
        commands that can return large amounts of textual data.

        This function handles gzip compressed responses that have the
        terminating line inside or outside the compressed data. From experience
        if the 'XFEATURE COMPRESS GZIP' command causes the terminating '.\\r\\n'
        to follow the compressed data and 'XFEATURE COMPRESS GZIP TERMINATOR'
        causes the terminator to be the last part of the compressed data (i.e
        the reply the gzipped version of the original reply - terminating line
        included)

        This function will produce that same output as the __info_plain_gen()
        function. In other words it takes care of decompression.

        Yields:
            A line of the info response.

        Raises:
            NNTPError: If data is required to be read from the socket and fails.
            NNTPDataError: If decompression fails.
        """
        self.__generating = True

        inflate = zlib.decompressobj(15+32)

        done, buf = False, fifo.Fifo()
        while not done:
            try:
                data = inflate.decompress(next(self.__buf_gen()))
            except zlib.error:
                raise NNTPDataError("Decompression failed")
            if data:
                buf.write(data)
            if inflate.unused_data:
                buf.write(inflate.unused_data)
            for line in buf:
                if line == ".\r\n":
                    done = True
                    break
                if line.startswith("."):
                    yield line[1:]
                yield line

        self.__generating = False

    def __info_yenczlib_gen(self):
        """Generator for the lines of a compressed info (textual) response.

        Compressed responses are an extension to the NNTP protocol supported by
        some usenet servers to reduce the bandwidth of heavily used range style
        commands that can return large amounts of textual data. The server
        returns that same data as it would for the uncompressed versions of the
        command the difference being that the data is zlib deflated and then
        yEnc encoded.

        This function will produce that same output as the info_gen()
        function. In other words it takes care of decoding and decompression.

        Yields:
            A line of the info response.

        Raises:
            NNTPError: If data is required to be read from the socket and fails.
            NNTPDataError: When there is an error parsing the yEnc header or
                trailer, if the CRC check fails or decompressing data fails.
        """

        escape = 0
        dcrc32 = 0
        inflate = zlib.decompressobj(-15)

        # header
        header = next(self.__info_plain_gen())
        if not header.startswith("=ybegin"):
            raise NNTPDataError("Bad yEnc header")

        # data
        buf, trailer = fifo.Fifo(), ""
        for line in self.__info_plain_gen():
            if line.startswith("=yend"):
                trailer = line
                continue
            data, escape, dcrc32 = yenc.decode(line, escape, dcrc32)
            try:
                data = inflate.decompress(data)
            except zlib.error:
                raise NNTPDataError("Decompression failed")
            if not data:
                continue
            buf.write(data)
            for l in buf:
                yield l

        # trailer
        if not trailer:
            raise NNTPDataError("Missing yEnc trailer")

        # expected crc32
        ecrc32 = yenc.crc32(trailer)
        if ecrc32 is None:
            raise NNTPDataError("Bad yEnc trailer")

        # check crc32
        if ecrc32 != dcrc32 & 0xffffffff:
            raise NNTPDataError("Bad yEnc CRC")

    def info_gen(self, code, message, compressed=False):
        """Dispatcher for the info generators.

        Determines which __info_*_gen() should be used based on the supplied
        parameters.

        Args:
            code: The status code for the command response.
            message: The status message for the command reponse.
            compressed: Force decompression. Useful for xz* commands.

        Returns:
            An info generator.
        """
        if "COMPRESS=GZIP" in message:
            return self.__info_gzip_gen()
        if compressed:
            return self.__info_yenczlib_gen()
        return self.__info_plain_gen()

    def info(self, code, message, compressed=False):
        """The complete content of an info response.

        This should only used for commands that return small or known amounts of
        data.

        Returns:
            A the complete content of a textual response.
        """
        return "".join([x for x in self.info_gen(code, message, compressed)])

    def command(self, verb, args=None):
        """Call a command on the server.

        If the user has not authenticated then authentication will be done
        as part of calling the command on the server.

        For commands that don't return a status message the status message
        will default to an empty string.

        Args:
            verb: The verb of the command to call.
            args: The arguments of the command as a string (default None).

        Returns:
            A tuple of status code (as an integer) and status message.

        Note:
            You can run raw commands by supplying the full command (including
            args) in the verb.

        Note: Although it is possible you shouldn't issue more than one command
            at a time by adding newlines to the verb as it will most likely lead
            to undesirable results.
        """
        if self.__generating:
            raise NNTPSyncError("Command issued while a generator is active")

        cmd = verb
        if args:
            cmd += " " + args
        cmd += "\r\n"

        self.socket.sendall(cmd)

        try:
            code, message = self.status()
        except NNTPTemporaryError as e:
            if e.code() != 480:
                raise e
            code, message = self.command("AUTHINFO USER", self.username)
            if code == 381:
                code, message = self.command("AUTHINFO PASS", self.password)
            if code != 281:
                raise NNTPReplyError(code, message)
            code, message = self.command(verb, args)

        return code, message

    def close(self):
        """Closes the connection at the client.

        Once this method has been called, no other methods of the NNTPClient object
        should be called.
        """
        self.socket.close()


class NNTPClient(BaseNNTPClient):
    """NNTP NNTPClient.

    Implements many of the commands that are commonly used by current usenet
    servers. Including handling commands that use compressed responses.

    Implements generators for commands for which generators are likely to
    yield (bad pun warning) perfomance gains. These gains will be in the form
    of lower memory consumption and the added ability to process and receive
    data in parallel. If you are using commands that can take a range as an
    argument or can return large amounts of data there should be a _gen()
    version of the command and it should be used in preference to the standard
    version.

    Note: All commands can raise the following exceptions:
            NNTPError
            NNTPProtocolError
            NNTPPermanentError
            NNTPReplyError
            IOError (socket.error)

    Note: All commands that use compressed responses can also raise an
        NNTPDataError.
    """

    def __init__(self, host, port=119, username="", password="", timeout=30, use_ssl=False, reader=True):
        """Constructor for NNTP NNTPClient.

        Connects to usenet server..

        Args:
            host: Hostname for usenet server.
            port: Port for usenet server.
            username: Username for usenet account (default "")
            password: Password for usenet account (default "")
            timeout: Connection timeout (default 30 seconds)
            use_ssl: Should we use ssl (default False)
            reader: Use reader mode

        Raises:
            socket.error: On error in underlying socket and/or ssl wrapper. See
                socket and ssl modules for further details.
            NNTPReplyError: On bad response code from server.
        """
        super(NNTPClient, self).__init__(host, port, username, password, timeout, use_ssl)

        # reader
        if reader:
            self.mode_reader()


    # session administration commands

    def capabilities(self, keyword=None):
        """CAPABILITIES command.

        Determines the capabilities of the server.

        Although RFC3977 states that this is a required command for servers to
        implement not all servers do, so expect that NNTPPermanentError may be
        raised when this command is issued.

        See <http://tools.ietf.org/html/rfc3977#section-5.2>

        Args:
            keyword: Passed directly to the server, however, this is unused by
                the server according to RFC3977.

        Returns:
            A list of capabilities supported by the server. The VERSION
            capability is the first capability in the list.
        """
        args = keyword

        code, message = self.command("CAPABILITIES", args)
        if code != 101:
            raise NNTPReplyError(code, message)

        return [x.strip() for x in self.info_gen(code, message)]

    def mode_reader(self):
        """MODE READER command.

        Instructs a mode-switching server to switch modes.

        See <http://tools.ietf.org/html/rfc3977#section-5.3>

        Returns:
            Boolean value indicating whether posting is allowed or not.
        """
        code, message = self.command("MODE READER")
        if not code in [200, 201]:
            raise NNTPReplyError(code, message)

        return code == 200

    def quit(self):
        """QUIT command.

        Tells the server to close the connection. After the server acknowledges
        the request to quit the connection is closed both at the server and
        client. Only useful for graceful shutdown. If you are in a generator
        use close() instead.

        Once this method has been called, no other methods of the NNTPClient
        object should be called.

        See <http://tools.ietf.org/html/rfc3977#section-5.4>
        """
        code, message = self.command("QUIT")
        if code != 205:
            raise NNTPReplyError(code, message)

        self.socket.close()


    # information commands

    def date(self):
        """DATE command.

        Coordinated Universal time from the perspective of the usenet server.
        It can be used to provide information that might be useful when using
        the NEWNEWS command.

        See <http://tools.ietf.org/html/rfc3977#section-7.1>

        Returns:
            The UTC time according to the server as a datetime object.

        Raises:
            NNTPDataError: If the timestamp can't be parsed.
        """
        code, message = self.command("DATE")
        if code != 111:
            raise NNTPReplyError(code, message)

        ts = date.datetimeobj(message, fmt="%Y%m%d%H%M%S")

        return ts

    def help(self):
        """HELP command.

        Provides a short summary of commands that are understood by the usenet
        server.

        See <http://tools.ietf.org/html/rfc3977#section-7.2>

        Returns:
            The help text from the server.
        """
        code, message = self.command("HELP")
        if code != 100:
            raise NNTPReplyError(code, message)

        return self.info(code, message)

    def newgroups_gen(self, timestamp):
        """Generator for the NEWGROUPS command.

        Generates a list of newsgroups created on the server since the specified
        timestamp.

        See <http://tools.ietf.org/html/rfc3977#section-7.3>

        Args:
            timestamp: Datetime object giving 'created since' datetime.

        Yields:
            A tuple containing the name, low water mark, high water mark,
            and status for the newsgroup.

        Note: If the datetime object supplied as the timestamp is naive (tzinfo
            is None) then it is assumed to be given as GMT.
        """
        if timestamp.tzinfo:
            ts = timestamp.asttimezone(date.TZ_GMT)
        else:
            ts = timestamp.replace(tzinfo=date.TZ_GMT)

        args = ts.strftime("%Y%m%d %H%M%S %Z")

        code, message = self.command("NEWGROUPS", args)
        if code != 231:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield utils.parse_newsgroup(line)

    def newgroups(self, timestamp):
        """NEWGROUPS command.

        Retreives a list of newsgroups created on the server since the specified
        timestamp. See newgroups_gen() for more details.

        See <http://tools.ietf.org/html/rfc3977#section-7.3>

        Args:
            timestamp: Datetime object giving 'created since' datetime.

        Returns:
            A list of tuples in the format given by newgroups_gen()
        """
        return [x for x in self.newgroups_gen(timestamp)]

    def newnews_gen(self, pattern, timestamp):
        """Generator for the NEWNEWS command.

        Generates a list of message-ids for articles created since the specified
        timestamp for newsgroups with names that match the given pattern.

        See <http://tools.ietf.org/html/rfc3977#section-7.4>

        Args:
            pattern: Glob matching newsgroups of intrest.
            timestamp: Datetime object giving 'created since' datetime.

        Yields:
            A message-id as string.

        Note: If the datetime object supplied as the timestamp is naive (tzinfo
            is None) then it is assumed to be given as GMT. If tzinfo is set
            then it will be converted to GMT by this function.
        """
        if timestamp.tzinfo:
            ts = timestamp.asttimezone(date.TZ_GMT)
        else:
            ts = timestamp.replace(tzinfo=date.TZ_GMT)

        args = pattern
        args += " " + ts.strftime("%Y%m%d %H%M%S %Z")

        code, message = self.command("NEWNEWS", args)
        if code != 230:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield line.strip()

    def newnews(self, pattern, timestamp):
        """NEWNEWS command.

        Retrieves a list of message-ids for articles created since the specified
        timestamp for newsgroups with names that match the given pattern. See
        newnews_gen() for more details.

        See <http://tools.ietf.org/html/rfc3977#section-7.4>

        Args:
            pattern: Glob matching newsgroups of intrest.
            timestamp: Datetime object giving 'created since' datetime.

        Returns:
            A list of message-ids as given by newnews_gen()
        """
        return [x for x in self.newnews_gen(pattern, timestamp)]


    # list commands

    def list_active_gen(self, pattern=None):
        """Generator for the LIST ACTIVE command.

        Generates a list of active newsgroups that match the specified pattern.
        If no pattern is specfied then all active groups are generated.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.3>

        Args:
            pattern: Glob matching newsgroups of intrest.

        Yields:
            A tuple containing the name, low water mark, high water mark,
            and status for the newsgroup.
        """
        args = pattern

        if args is None:
            cmd = "LIST"
        else:
            cmd = "LIST ACTIVE"

        code, message = self.command(cmd, args)
        if code != 215:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield utils.parse_newsgroup(line)

    def list_active(self, pattern=None):
        """LIST ACTIVE command.

        Retreives a list of active newsgroups that match the specified pattern.
        See list_active_gen() for more details.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.3>

        Args:
            pattern: Glob matching newsgroups of intrest.

        Returns:
            A list of tuples in the format given by list_active_gen()
        """
        return [x for x in self.list_active_gen(pattern)]

    def list_active_times_gen(self):
        """Generator for the LIST ACTIVE.TIMES command.

        Generates a list of newsgroups including the creation time and who
        created them.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.4>

        Yields:
            A tuple containing the name, creation date as a datetime object and
            creator as a string for the newsgroup.
        """
        code, message = self.command("LIST ACTIVE.TIMES")
        if code != 215:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            parts = line.split()
            try:
                name = parts[0]
                timestamp = date.datetimeobj_epoch(parts[1])
                creator = parts[2]
            except (IndexError, ValueError):
                raise NNTPDataError("Invalid LIST ACTIVE.TIMES")
            yield name, timestamp, creator

    def list_active_times(self):
        """LIST ACTIVE TIMES command.

        Retrieves a list of newsgroups including the creation time and who
        created them. See list_active_times_gen() for more details.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.4>

        Returns:
            A list of tuples in the format given by list_active_times_gen()
        """
        return [x for x in self.list_active_times_gen()]

    def list_distrib_pats_gen(self):
        """Generator for the LIST DISTRIB.PATS command.
        """
        raise NotImplementedError()

    def list_distrib_pats(self):
        """LIST DISTRIB.PATS command.
        """
        return [x for x in self.list_distrib_pats_gen()]

    def list_headers_gen(self, arg=None):
        """Generator for the LIST HEADERS command.
        """
        raise NotImplementedError()

    def list_headers(self, arg=None):
        """LIST HEADERS command.
        """
        return [x for x in self.list_headers_gen(arg)]

    def list_newsgroups_gen(self, pattern=None):
        """Generator for the LIST NEWSGROUPS command.

        Generates a list of newsgroups including the name and a short
        description.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.6>

        Args:
            pattern: Glob matching newsgroups of intrest.

        Yields:
            A tuple containing the name, and description for the newsgroup.
        """
        args = pattern

        code, message = self.command("LIST NEWSGROUPS", args)
        if code != 215:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            parts = line.strip().split()
            name, description = parts[0], ""
            if len(parts) > 1:
                description = parts[1]
            yield name, description

    def list_newsgroups(self, pattern=None):
        """LIST NEWSGROUPS command.

        Retrieves a list of newsgroups including the name and a short
        description. See list_newsgroups_gen() for more details.

        See <http://tools.ietf.org/html/rfc3977#section-7.6.6>

        Args:
            pattern: Glob matching newsgroups of intrest.

        Returns:
            A list of tuples in the format given by list_newsgroups_gen()
        """
        return [x for x in self.list_newsgroups_gen(pattern)]

    def list_overview_fmt_gen(self):
        """Generator for the LIST OVERVIEW.FMT

        See list_overview_fmt() for more information.

        Yields:
            An element in the list returned by list_overview_fmt().
        """
        code, message = self.command("LIST OVERVIEW.FMT")
        if code != 215:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            try:
                name, suffix = line.rstrip().split(":")
            except ValueError:
                raise NNTPDataError("Invalid LIST OVERVIEW.FMT")
            if suffix and not name:
                name, suffix = suffix, name
            if suffix and suffix != "full":
                raise NNTPDataError("Invalid LIST OVERVIEW.FMT")
            yield (name, suffix == "full")

    def list_overview_fmt(self):
        """LIST OVERVIEW.FMT command.
        """
        return [x for x in self.list_overview_fmt_gen()]

    def list_extensions_gen(self):
        """Generator for the LIST EXTENSIONS command.
        """
        code, message = self.command("LIST EXTENSIONS")
        if code != 202:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield line.strip()

    def list_extensions(self):
        """LIST EXTENSIONS command.
        """
        return [x for x in self.list_extensions_gen()]

    def list_gen(self, keyword=None, arg=None):
        """Generator for LIST command.

        See list() for more information.

        Yields:
            An element in the list returned by list().
        """
        if keyword:
            keyword = keyword.upper()

        if keyword is None or keyword == "ACTIVE":
            return self.list_active_gen(arg)
        if keyword == "ACTIVE.TIMES":
            return self.list_active_times_gen()
        if keyword == "DISTRIB.PATS":
            return self.list_distrib_pats_gen()
        if keyword == "HEADERS":
            return self.list_headers_gen(arg)
        if keyword == "NEWSGROUPS":
            return self.list_newsgroups_gen(arg)
        if keyword == "OVERVIEW.FMT":
            return self.list_overview_fmt_gen()
        if keyword == "EXTENSIONS":
            return self.list_extensions_gen()

        raise NotImplementedError()

    def list(self, keyword=None, arg=None):
        """LIST command.

        A wrapper for all of the other list commands. The output of this command
        depends on the keyword specified. The output format for each keyword can
        be found in the list function that corresponds to the keyword.

        Args:
            keyword: Information requested.
            arg: Pattern or keyword specific argument.

        Note: Keywords supported by this function are include ACTIVE,
            ACTIVE.TIMES, DISTRIB.PATS, HEADERS, NEWSGROUPS, OVERVIEW.FMT and
            EXTENSIONS.

        Raises:
            NotImplementedError: For unsupported keywords.
        """
        return [x for x in self.list_gen(keyword, arg)]

    def group(self, name):
        """GROUP command.
        """
        args = name

        code, message = self.command("GROUP", args)
        if code != 211:
            raise NNTPReplyError(code, message)

        parts = message.split(None, 4)
        try:
            total = int(parts[0])
            first = int(parts[1])
            last  = int(parts[2])
            group = parts[3]
        except (IndexError, ValueError):
            raise NNTPDataError("Invalid GROUP status '%s'" % message)

        return total, first, last, group

    def next(self):
        """NEXT command.
        """
        code, message = self.command("NEXT")
        if code != 223:
            raise NNTPReplyError(code, message)

        parts = message.split(None, 3)
        try:
            article = int(parts[0])
            ident = parts[1]
        except (IndexError, ValueError):
            raise NNTPDataError("Invalid NEXT status")

        return article, ident

    def last(self):
        """LAST command.
        """
        code, message = self.command("LAST")
        if code != 223:
            raise NNTPReplyError(code, message)

        parts = message.split(None, 3)
        try:
            article = int(parts[0])
            ident = parts[1]
        except (IndexError, ValueError):
            raise NNTPDataError("Invalid LAST status")

        return article, ident

    # TODO: Validate yEnc body
    def article(self, msgid_article=None, decode=None):
        """ARTICLE command.
        """
        args = None
        if msgid_article is not None:
            args = utils.unparse_msgid_article(msgid_article)

        code, message = self.command("ARTICLE", args)
        if code != 220:
            raise NNTPReplyError(code, message)

        parts = message.split(None, 1)

        try:
            articleno = int(parts[0])
        except ValueError:
            raise NNTPProtocolError(message)

        # headers
        headers = utils.parse_headers(self.info_gen(code, message))

        # decoding setup
        decode = "yEnc" in headers.get("subject", "")
        escape = 0
        crc32 = 0

        # body
        body = []
        for line in self.info_gen(code, message):

            # decode body if required
            if decode:
                if line.startswith("=y"):
                    continue
                line, escape, crc32 = yenc.decode(line, escape, crc32)

            body.append(line)

        return articleno, headers, "".join(body)

    def head(self, msgid_article=None):
        """HEAD command.
        """
        args = None
        if msgid_article is not None:
            args = utils.unparse_msgid_article(msgid_article)

        code, message = self.command("HEAD", args)
        if code != 221:
            raise NNTPReplyError(code, message)

        return utils.parse_headers(self.info_gen(code, message))

    # TODO: Support yEnc article body validation
    def body(self, msgid_article=None, decode=False):
        """BODY command.
        """
        args = None
        if msgid_article is not None:
            args = utils.unparse_msgid_article(msgid_article)

        code, message = self.command("BODY", args)
        if code != 222:
            raise NNTPReplyError(code, message)

        escape = 0
        crc32 = 0

        body = []
        for line in self.info_gen(code, message):

            # decode body if required
            if decode:
                if line.startswith("=y"):
                    continue
                line, escape, crc32 = yenc.decode(line, escape, crc32)

            # body
            body.append(line)

        return "".join(body)

    def xgtitle(self, pattern=None):
        """XGTITLE command.
        """
        args = pattern

        code, message = self.command("XGTITLE", args)
        if code != 282:
            raise NNTPReplyError(code, message)

        return self.info(code, message)

    def xhdr(self, header, msgid_range=None):
        """XHDR command.
        """
        args = header
        if range is not None:
            args += " " + utils.unparse_msgid_range(msgid_range)

        code, message = self.command("XHDR", args)
        if code != 221:
            raise NNTPReplyError(code, message)

        return self.info(code, message)

    def xzhdr(self, header, msgid_range=None):
        """XZHDR command.

        Args:
            msgid_range: A message-id as a string, or an article number as an
                integer, or a tuple of specifying a range of article numbers in
                the form (first, [last]) - if last is omitted then all articles
                after first are included. A msgid_range of None (the default)
                uses the current article.
        """
        args = header
        if msgid_range is not None:
            args += " " + utils.unparse_msgid_range(msgid_range)

        code, message = self.command("XZHDR", args)
        if code != 221:
            raise NNTPReplyError(code, message)

        return self.info(code, message, compressed=True)

    def xover_gen(self, range=None):
        """Generator for the XOVER command.

        The XOVER command returns information from the overview database for
        the article(s) specified.

        <http://tools.ietf.org/html/rfc2980#section-2.8>

        Args:
            range: An article number as an integer, or a tuple of specifying a
                range of article numbers in the form (first, [last]). If last is
                omitted then all articles after first are included. A range of
                None (the default) uses the current article.

        Returns:
            A list of fields as given by the overview database for each
            available article in the specified range. The fields that are
            returned can be determined using the LIST OVERVIEW.FMT command if
            the server supports it.

        Raises:
            NNTPReplyError: If no such article exists or the currently selected
                newsgroup is invalid.
        """
        args = None
        if range is not None:
            args = utils.unparse_range(range)

        code, message = self.command("XOVER", args)
        if code != 224:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield line.rstrip().split("\t")

    def xover(self, range=None):
        """The XOVER command.

        The XOVER command returns information from the overview database for
        the article(s) specified.

        <http://tools.ietf.org/html/rfc2980#section-2.8>

        Args:
            range: An article number as an integer, or a tuple of specifying a
                range of article numbers in the form (first, [last]). If last is
                omitted then all articles after first are included. A range of
                None (the default) uses the current article.

        Returns:
            A table (list of lists) of articles and their fields as given by the
            overview database for each available article in the specified range.
            The fields that are given can be determined using the LIST
            OVERVIEW.FMT command if the server supports it.

        Raises:
            NNTPReplyError: If no such article exists or the currently selected
                newsgroup is invalid.
        """
        return [x for x in self.xover_gen(range)]

    def xzver_gen(self, range=None):
        """Generator for the XZVER command.

        The XZVER command returns information from the overview database for
        the article(s) specified. It is part of the compressed headers
        extensions that are supported by some usenet servers. It is the
        compressed version of the XOVER command.

        <http://helpdesk.astraweb.com/index.php?_m=news&_a=viewnews&newsid=9>

        Args:
            range: An article number as an integer, or a tuple of specifying a
                range of article numbers in the form (first, [last]). If last is
                omitted then all articles after first are included. A range of
                None (the default) uses the current article.

        Returns:
            A list of fields as given by the overview database for each
            available article in the specified range. The fields that are
            returned can be determined using the LIST OVERVIEW.FMT command if
            the server supports it.

        Raises:
            NNTPTemporaryError: If no such article exists or the currently
                selected newsgroup is invalid.
            NNTPDataError: If the compressed response cannot be decoded.
        """
        args = None
        if range is not None:
            args = utils.unparse_range(range)

        code, message = self.command("XZVER", args)
        if code != 224:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message, True):
            yield line.rstrip().split("\t")

    def xzver(self, range=None):
        """XZVER command.

        The XZVER command returns information from the overview database for
        the article(s) specified. It is part of the compressed headers
        extensions that are supported by some usenet servers. It is the
        compressed version of the XOVER command.

        <http://helpdesk.astraweb.com/index.php?_m=news&_a=viewnews&newsid=9>

        Args:
            range: An article number as an integer, or a tuple of specifying a
                range of article numbers in the form (first, [last]). If last is
                omitted then all articles after first are included. A range of
                None (the default) uses the current article.

        Returns:
            A list of fields as given by the overview database for each
            available article in the specified range. The fields that are
            returned can be determined using the LIST OVERVIEW.FMT command if
            the server supports it.

        Raises:
            NNTPTemporaryError: If no such article exists or the currently
                selected newsgroup is invalid.
            NNTPDataError: If the compressed response cannot be decoded.
        """
        return [x for x in self.xzver_gen(range)]

    def xpat_gen(self, header, msgid_range, *pattern):
        """Generator for the XPAT command.
        """
        args = " ".join(
            [header, utils.unparse_msgid_range(msgid_range)] + list(pattern)
        )

        code, message = self.command("XPAT", args)
        if code != 221:
            raise NNTPReplyError(code, message)

        for line in self.info_gen(code, message):
            yield line.strip()

    def xpat(self, header, id_range, *pattern):
        """XPAT command.
        """
        return [x for x in self.xpat_gen(header, id_range, *pattern)]

    def xfeature_compress_gzip(self, terminator=False):
        """XFEATURE COMPRESS GZIP command.
        """
        args = "TERMINATOR" if terminator else None

        code, message = self.command("XFEATURE COMPRESS GZIP", args)
        if code != 290:
            raise NNTPReplyError(code, message)

        return True

    def post(self, headers={}, body=""):
        """POST command.

        Args:
            headers: A dictionary of headers.
            body: A string or file like object containing the post content.

        Raises:
            NNTPDataError: If binary characters are detected in the message
                body.

        Returns:
            A value that evaluates to true if posting the message succeeded.
            (See note for further details)

        Note:
            '\\n' line terminators are converted to '\\r\\n'

        Note:
            Though not part of any specification it is common for usenet servers
            to return the message-id for a successfully posted message. If a
            message-id is identified in the response from the server then that
            message-id will be returned by the function, otherwise True will be
            returned.

        Note:
            Due to protocol issues if illegal characters are found in the body
            the message will still be posted but will be truncated as soon as
            an illegal character is detected. No illegal characters will be sent
            to the server. For information illegal characters include embedded
            carriage returns '\\r' and null characters '\\0' (because this
            function converts line feeds to CRLF, embedded line feeds are not an
            issue)
        """
        code, message = self.command("POST")
        if code != 340:
            raise NNTPReplyError(code, message)

        # send headers
        hdrs = utils.unparse_headers(headers)
        self.socket.sendall(hdrs)

        if isinstance(body, basestring):
            body = cStringIO.StringIO(body)

        # send body
        illegal = False
        for line in body:
            if line.startswith("."):
                line = "." + line
            if line.endswith("\r\n"):
                line = line[:-2]
            elif line.endswith("\n"):
                line = line[:-1]
            if any(c in line for c in "\0\r"):
                illegal = True
                break
            self.socket.sendall(line + "\r\n")
        self.socket.sendall(".\r\n")

        # get status
        code, message = self.status()

        # check if illegal characters were detected
        if illegal:
            raise NNTPDataError("Illegal characters found")

        # check status
        if code != 240:
            raise NNTPReplyError(code, message)

        # return message-id possible
        message_id = message.split(None, 1)[0]
        if message_id.startswith("<") and message_id.endswith(">"):
            return message_id

        return True

# testing
if __name__ == "__main__":

    import sys
    import hashlib

    log = sys.stdout.write

    try:
        host = sys.argv[1]
        port = int(sys.argv[2])
        username = sys.argv[3]
        password = sys.argv[4]
        use_ssl = int(sys.argv[5])
    except:
        log("%s <host> <port> <username> <password> <ssl(0|1)>\n" % sys.argv[0])
        sys.exit(1)

    nntp_client = NNTPClient(host, port, username, password, use_ssl=use_ssl, reader=False)

    try:
        log("HELP\n")
        try:
            log("%s\n" % nntp_client.help())
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("DATE\n")
        try:
            log("%s\n" % nntp_client.date())
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("NEWGROUPS\n")
        try:
            log("%s\n" % nntp_client.newgroups(datetime.datetime.utcnow() - datetime.timedelta(days=50)))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("NEWNEWS\n")
        try:
            log("%s\n" % nntp_client.newnews("alt.binaries.*", datetime.datetime.utcnow() - datetime.timedelta(minutes=1)))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("CAPABILITIES\n")
        try:
            log("%s\n" % nntp_client.capabilities())
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("GROUP misc.test\n")
        try:
            total, first, last, name = nntp_client.group("misc.test")
            log("%d %d %d %s\n" % (total, first, last, name))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("HEAD\n")
        try:
            log("%r\n" % nntp_client.head(last))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("BODY\n")
        try:
            log("%r\n" % nntp_client.body(last))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("ARTICLE\n")
        try:
            result = nntp_client.article(last, False)
            log("%d\n%s\n%r\n" % (result[0], result[1], result[2]))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("ARTICLE (auto yEnc decode)\n")
        try:
            result = nntp_client.article(last)
            log("%d\n%s\n%r\n" % (result[0], result[1], result[2]))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XHDR Date %d-%d\n" % (last-10, last))
        try:
            log("%s\n" % nntp_client.xhdr("Date", (last-10, last)))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XZHDR Date %d-%d\n" % (last-10, last))
        try:
            log("%s\n" % nntp_client.xzhdr("Date", (last-10, last)))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XOVER %d-%d\n" % (last-10, last))
        try:
            result = nntp_client.xover((last-10, last))
            log("Entries %d Hash %s\n" % (len(result), hashlib.md5("".join(["".join(x) for x in result])).hexdigest()))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XZVER %d-%d\n" % (last-10, last))
        try:
            result = nntp_client.xzver((last-10, last))
            log("Entries %d Hash %s\n" % (len(result), hashlib.md5("".join(["".join(x) for x in result])).hexdigest()))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XFEATURE COMPRESS GZIP\n")
        try:
            log("%s\n" % nntp_client.xfeature_compress_gzip())
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XOVER %d-%d\n" % (last-10, last))
        try:
            result = nntp_client.xover((last-10, last))
            log("Entries %d Hash %s\n" % (len(result), hashlib.md5("".join(["".join(x) for x in result])).hexdigest()))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XFEATURE COMPRESS GZIP TERMINATOR\n")
        try:
            log("%s\n" % nntp_client.xfeature_compress_gzip())
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("XOVER %d-%d\n" % (last-10, last))
        try:
            result = nntp_client.xover((last-10, last))
            log("Entries %d Hash %s\n" % (len(result), hashlib.md5("".join(["".join(x) for x in result])).hexdigest()))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST\n")
        try:
            log("Entries %d\n" % len(nntp_client.list()))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST ACTIVE\n")
        try:
            log("Entries %d\n" % len(nntp_client.list("ACTIVE")))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST ACTIVE alt.binaries.*\n")
        try:
            log("Entries %d\n" % len(nntp_client.list("ACTIVE", "alt.binaries.*")))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST ACTIVE.TIMES\n")
        try:
            log("Entries %d\n" % len(nntp_client.list("ACTIVE.TIMES")))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST NEWSGROUPS\n")
        try:
            log("Entries %d\n" % len(nntp_client.list("NEWSGROUPS")))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST NEWSGROUPS alt.binaries.*\n")
        try:
            log("Entries %d\n" % len(nntp_client.list("NEWSGROUPS", "alt.binaries.*")))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST OVERVIEW.FMT\n")
        try:
            log("%s\n" % nntp_client.list("OVERVIEW.FMT"))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("LIST EXTENSIONS\n")
        try:
            log("%s\n" % nntp_client.list("EXTENSIONS"))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("POST (with illegal characters)\n")
        try:
            log("%s\n" % nntp_client.post(
                iodict.IODict({
                    "From": "\"pynntp\" <pynntp@not.a.real.doma.in>",
                    "Newsgroups": "misc.test",
                    "Subject": "pynntp test article",
                    "Organization": "pynntp",
                }),
                "pip install pynntp\r\nthis\0contains\rillegal\ncharacters"
            ))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("POST\n")
        try:
            log("%s\n" % nntp_client.post(
                iodict.IODict({
                    "From": "\"pynntp\" <pynntp@not.a.real.doma.in>",
                    "Newsgroups": "misc.test",
                    "Subject": "pynntp test article",
                    "Organization": "pynntp",
                }),
                "pip install pynntp"
            ))
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

        log("QUIT\n")
        try:
            nntp_client.quit()
        except NNTPError as e:
            log("%s\n" % e)
        log("\n")

    finally:
        log("CLOSING CONNECTION\n")
        nntp_client.close()
