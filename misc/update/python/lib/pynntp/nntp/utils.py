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

import cStringIO
import iodict

def unparse_msgid_article(obj):
    """Unparse a message-id or article number argument.

    Args:
        obj: A messsage id or an article number.

    Returns:
        The message id or article number as a string.
    """
    return str(obj)

def parse_msgid_article(obj):
    """Parse a message-id or article number argument.

    Args:
        str: Message id or article as a string.

    Returns:
        The message id as a string or article number as an integer.
    """
    try:
        return int(obj)
    except ValueError:
        pass
    return obj

def unparse_range(obj):
    """Unparse a range argument.

    Args:
        obj: An article range. There are a number of valid formats; an integer
            specifying a single article or a tuple specifying an article range.
            If the range doesn't give a start article then all articles up to
            the specified last article are included. If the range doesn't
            specify a last article then all articles from the first specified
            article up to the current last article for the group are included.

    Returns:
        The range as a string that can be used by an NNTP command.

    Note: Sample valid formats.
        4678
        (,5234)
        (4245,)
        (4245, 5234)
    """
    if isinstance(obj, (int, long)):
        return str(obj)

    if isinstance(obj, tuple):
        arg = str(obj[0]) + "-"
        if len(obj) > 1:
            arg += str(obj[1])
        return arg

    raise ValueError("Must be an integer or tuple")

def parse_range(obj):
    """Parse a range argument.

    Args:
        obj: An article range as a string.

    Returns:
        The range as a string that can be used by an NNTP command.

    Raises:
        ValueError: If obj is not a valid range format.

    Note: Sample valid formats.
        4678
        (,5234)
        (4245,)
        (4245, 5234)
    """
    if isinstance(obj, (int, long)):
        return str(obj)

    if isinstance(obj, tuple):
        arg = str(obj[0]) + "-"
        if len(obj) > 1:
            arg += str(obj[1])
        return arg

    raise ValueError("Must be an integer or tuple")

def unparse_msgid_range(obj):
    """Unparse a message-id or range argument.

    Args:
        obj: A message id as a string or a range as specified by
            unparse_range().

    Raises:
        ValueError: If obj is not a valid message id or range format. See
            unparse_range() for valid range formats.

    Returns:
        A message id or range as a string that can be used by an NNTP command.
    """
    if isinstance(obj, basestring):
        return obj

    return unparse_range(obj)

def parse_newsgroup(line):
    """Parse a newsgroup info line to python types.

    Args:
        line: An info response line containing newsgroup info.

    Returns:
        A tuple of group name, low-water as integer, high-water as integer and
        posting status.

    Raises:
        ValueError: If the newsgroup info cannot be parsed.

    Note:
        Posting status is a character is one of (but not limited to):
            "y" posting allowed
            "n" posting not allowed
            "m" posting is moderated
    """
    parts = line.split()
    try:
        group = parts[0]
        low = int(parts[1])
        high = int(parts[2])
        status = parts[3]
    except (IndexError, ValueError):
        raise ValueError("Invalid newsgroup info")
    return group, low, high, status

def parse_header(line):
    """Parse a header line.

    Args:
        line: A header line as a string.

    Returns:
        None if end of headers is found. A string giving the continuation line
        if a continuation is found. A tuple of name, value when a header line is
        found.

    Raises:
        ValueError: If the line cannot be parsed as a header.
    """
    if not line or line == "\r\n":
        return None
    if line[0] in " \t":
        return line[1:].rstrip()
    name, value = line.split(":", 1)
    return (name.strip(), value.strip())

def parse_headers(obj):
    """Parse a string a iterable object (including file like objects) to a
    python dictionary.

    Args:
        obj: An iterable object including file-like objects.

    Returns:
        An dictionary of headers. If a header is repeated then the last value
        for that header is given.

    Raises:
        ValueError: If the first line is a continuation line or the headers
            cannot be parsed.
    """
    if isinstance(obj, basestring):
        obj = cStringIO.StringIO(obj)
    hdrs = []
    for line in obj:
        hdr = parse_header(line)
        if not hdr:
            break
        if isinstance(hdr, basestring):
            if not hdrs:
                raise ValueError("First header is a continuation")
            hdrs[-1] = (hdrs[-1][0], hdrs[-1][1] + hdr)
            continue
        hdrs.append(hdr)
    return iodict.IODict(hdrs)

def unparse_header(name, value):
    """Parse a name value tuple to a header string.

    Args:
        name: The header name.
        value: the header value.

    Returns:
        The header as a string.
    """
    return ": ".join([name, value]) + "\r\n"

def unparse_headers(hdrs):
    """Parse a dictionary of headers to a string.

    Args:
        hdrs: A dictionary of headers.

    Returns:
        The headers as a string that can be used in an NNTP POST.
    """
    return "".join([unparse_header(n, v) for n, v in hdrs.items()]) + "\r\n"
