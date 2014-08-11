#!/usr/bin/python
"""
Basic yEnc decoder.
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

import re
import zlib
import struct
import binascii


_crc32_re = re.compile(r"\s+crc(?:32)?=([0-9a-fA-F]{8})")

def crc32(trailer):

    match = _crc32_re.search(trailer)
    if not match:
        return None

    buf = binascii.unhexlify(match.group(1))

    return struct.unpack(">I", buf)[0]


def decode(buff, escape=0, crc32=0):

    decoded = ""

    for c in buff:
        byte = ord(c)
        if escape:
            byte = (byte - 106) & 0xff
            escape = 0
        elif byte == 0x3d:
            escape = 1
            continue
        elif byte == 0x0d or byte == 0x0a:
            continue
        else:
            byte = (byte - 42) & 0xff
        decoded += chr(byte)

    crc32 = zlib.crc32(decoded, crc32)

    return decoded, escape, crc32
