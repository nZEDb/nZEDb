#!/usr/bin/python
"""
A reasonably efficient FIFO buffer.
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

#NOTE: docstrings beed to be added

_DISCARD_SIZE = 0xffff

class Fifo(object):

    def __init__(self, data="", eol="\r\n"):

        self.buf = data
        self.eol = eol
        self.buflist = []
        self.pos = 0

    def __len__(self):

        return len(self.buf) - self.pos

    def __iter__(self):

        return self

    def __discard(self):

        if self.pos > _DISCARD_SIZE:
            self.buf = self.buf[self.pos:]
            self.pos = 0

    def __append(self):

        self.buf += "".join(self.buflist)
        self.buflist = []

    def clear(self):

        self.buf = ""
        self.buflist = []
        self.pos = 0

    def write(self, data):

        self.buflist.append(data)

    def read(self, length=0):

        self.__append()

        if 0 < length < len(self):
            newpos = self.pos + length
            data = self.buf[self.pos:newpos]
            self.pos = newpos
            self.__discard()
            return data

        data = self.buf[self.pos:]
        self.clear()
        return data

    def readline(self):

        self.__append()

        i = self.buf.find(self.eol, self.pos)
        if i < 0:
            return ""

        newpos = i + len(self.eol)
        data = self.buf[self.pos:newpos]
        self.pos = newpos
        self.__discard()
        return data

    def readuntil(self, token, size=0):

        self.__append()

        i = self.buf.find(token, self.pos)
        if i < 0:
            index = max(len(token) - 1, size)
            newpos = max(len(self.buf) - index, self.pos)
            data = self.buf[self.pos:newpos]
            self.pos = newpos
            self.__discard()
            return False, data

        newpos = i + len(token)
        data = self.buf[self.pos:newpos]
        self.pos = newpos
        self.__discard()
        return True, data

    def peek(self, length=0):

        self.__append()

        if 0 < length < len(self):
            newpos = self.pos + length
            return self.buf[self.pos:newpos]

        return self.buf[self.pos:]

    def peekline(self):

        self.__append()

        i = self.buf.find(self.eol, self.pos)
        if i < 0:
            return ""

        newpos = i + len(self.eol)
        return self.buf[self.pos:newpos]

    def peekuntil(self, token, size=0):

        self.__append()

        i = self.buf.find(token, self.pos)
        if i < 0:
            index = max(len(token) - 1, size)
            newpos = max(len(self.buf) - index, self.pos)
            return False, self.buf[self.pos:newpos]

        newpos = i + len(token)
        return True, self.buf[self.pos:newpos]

    def next(self):

        line = self.readline()
        if not line:
            raise StopIteration()

        return line
