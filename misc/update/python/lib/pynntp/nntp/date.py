#!/usr/bin/python
"""
Date utilities to do fast datetime parsing.
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

# TODO: At the moment this has been targeted toward the datetime formats used by
#       NNTP as it was developed for use in a NNTP reader. There is, however, no
#       reason why this module could not be extended to include other formats.

import calendar
import datetime
import dateutil.parser
import dateutil.tz

class _tzgmt(dateutil.tz.tzutc):
    """GMT timezone.
    """

    def tzname(self, dt):
        return "GMT"

TZ_LOCAL = dateutil.tz.tzlocal()
"""Local timezone (at the time the module was loaded)"""

TZ_UTC = dateutil.tz.tzutc()
"""UTC timezone."""

TZ_GMT = _tzgmt()
"""GMT timezone."""

_months = dict(
    jan=1, feb=2, mar=3, apr=4, may=5, jun=6,
    jul=7, aug=8, sep=9, oct=10,nov=11,dec=12
)
"""Conversion dictionary for english abbreviated month to integer."""

def _offset(value):
    """Parse timezone to offset in seconds.

    Args:
        value: A timezone in the '+0000' format. An integer would also work.

    Returns:
        The timezone offset from GMT in seconds as an integer.
    """
    o = int(value)
    if o == 0:
        return 0
    a = abs(o)
    s = a*36+(a%100)*24
    return (o//a)*s

def timestamp_d_b_Y_H_M_S(value):
    """Convert timestamp string to time in seconds since epoch.

    Timestamps strings like '18 Jun 2013 12:00:00 GMT' are able to be converted
    by this function.

    Args:
        value: A timestamp string in the format '%d %b %Y %H:%M:%S GMT'.

    Returns:
        The time in seconds since epoch as an integer.

    Raises:
        ValueError: If timestamp is invalid.
        KeyError: If the abbrieviated month is invalid.

    Note: The timezone is ignored it is simply assumed to be UTC/GMT.
    """
    d, b, Y, t, Z = value.split()
    H, M, S = t.split(":")
    return int(calendar.timegm((
        int(Y), _months[b.lower()], int(d), int(H), int(M), int(S), 0, 0, 0
    )))

def datetimeobj_d_b_Y_H_M_S(value):
    """Convert timestamp string to a datetime object.

    Timestamps strings like '18 Jun 2013 12:00:00 GMT' are able to be converted
    by this function.

    Args:
        value: A timestamp string in the format '%d %b %Y %H:%M:%S GMT'.

    Returns:
        A datetime object.

    Raises:
        ValueError: If timestamp is invalid.
        KeyError: If the abbrieviated month is invalid.

    Note: The timezone is ignored it is simply assumed to be UTC/GMT.
    """
    d, b, Y, t, Z = value.split()
    H, M, S = t.split(":")
    return datetime.datetime(
        int(Y), _months[b.lower()], int(d), int(H), int(M), int(S), tzinfo=TZ_GMT
    )

def timestamp_a__d_b_Y_H_M_S_z(value):
    """Convert timestamp string to time in seconds since epoch.

    Timestamps strings like 'Tue, 18 Jun 2013 22:00:00 +1000' are able to be
    converted by this function.

    Args:
        value: A timestamp string in the format '%a, %d %b %Y %H:%M:%S %z'.

    Returns:
        The time in seconds since epoch as an integer.

    Raises:
        ValueError: If timestamp is invalid.
        KeyError: If the abbrieviated month is invalid.
    """
    a, d, b, Y, t, z = value.split()
    H, M, S = t.split(":")
    return int(calendar.timegm((
        int(Y), _months[b.lower()], int(d), int(H), int(M), int(S), 0, 0, 0
    ))) - _offset(z)

def datetimeobj_a__d_b_Y_H_M_S_z(value):
    """Convert timestamp string to a datetime object.

    Timestamps strings like 'Tue, 18 Jun 2013 22:00:00 +1000' are able to be
    converted by this function.

    Args:
        value: A timestamp string in the format '%a, %d %b %Y %H:%M:%S %z'.

    Returns:
        A datetime object.

    Raises:
        ValueError: If timestamp is invalid.
        KeyError: If the abbrieviated month is invalid.
    """
    a, d, b, Y, t, z = value.split()
    H, M, S = t.split(":")
    return datetime.datetime(
        int(Y), _months[b.lower()], int(d), int(H), int(M), int(S),
        tzinfo=dateutil.tz.tzoffset(None, _offset(z))
    )

def timestamp_YmdHMS(value):
    """Convert timestamp string to time in seconds since epoch.

    Timestamps strings like '20130618120000' are able to be converted by this
    function.

    Args:
        value: A timestamp string in the format '%Y%m%d%H%M%S'.

    Returns:
        The time in seconds since epoch as an integer.

    Raises:
        ValueError: If timestamp is invalid.

    Note: The timezone is assumed to be UTC/GMT.
    """
    i = int(value)
    S = i
    M = S//100
    H = M//100
    d = H//100
    m = d//100
    Y = m//100
    return int(calendar.timegm((
        Y % 10000, m % 100, d % 100, H % 100, M % 100, S % 100, 0, 0, 0)
    ))

def datetimeobj_YmdHMS(value):
    """Convert timestamp string to a datetime object.

    Timestamps strings like '20130618120000' are able to be converted by this
    function.

    Args:
        value: A timestamp string in the format '%Y%m%d%H%M%S'.

    Returns:
        A datetime object.

    Raises:
        ValueError: If timestamp is invalid.

    Note: The timezone is assumed to be UTC/GMT.
    """
    i = int(value)
    S = i
    M = S//100
    H = M//100
    d = H//100
    m = d//100
    Y = m//100
    return datetime.datetime(
        Y % 10000, m % 100, d % 100, H % 100, M % 100, S % 100, tzinfo=TZ_GMT
    )

def timestamp_epoch(value):
    """Convert timestamp string to a datetime object.

    Timestamps strings like '1383470155' are able to be converted by this
    function.

    Args:
        value: A timestamp string as seconds since epoch.

    Returns:
        The time in seconds since epoch as an integer.
    """
    return int(value)

def datetimeobj_epoch(value):
    """Convert timestamp string to a datetime object.

    Timestamps strings like '1383470155' are able to be converted by this
    function.

    Args:
        value: A timestamp string as seconds since epoch.

    Returns:
        A datetime object.

    Raises:
        ValueError: If timestamp is invalid.
    """
    return datetime.datetime.utcfromtimestamp(int(value)).replace(tzinfo=TZ_GMT)

def timestamp_fmt(value, fmt):
    """Convert timestamp string to time in seconds since epoch.

    Wraps the datetime.datetime.strptime(). This is slow use the other
    timestamp_*() functions if possible.

    Args:
        value: A timestamp string.
        fmt: A timestamp format string.

    Returns:
        The time in seconds since epoch as an integer.
    """
    return int(calendar.timegm(
        datetime.datetime.strptime(value, fmt).utctimetuple()
    ))

def datetimeobj_fmt(value, fmt):
    """Convert timestamp string to a datetime object.

    Wrapper for datetime.datetime.strptime(). This is slow use the other
    timestamp_*() functions if possible.

    Args:
        value: A timestamp string.
        fmt: A timestamp format string.

    Returns:
        A datetime object.
    """
    return datetime.datetime.strptime(value, fmt)

def timestamp_any(value):
    """Convert timestamp string to time in seconds since epoch.

    Most timestamps strings are supported in fact this wraps the
    dateutil.parser.parse() method. This is SLOW use the other timestamp_*()
    functions if possible.

    Args:
        value: A timestamp string.

    Returns:
        The time in seconds since epoch as an integer.
    """
    return int(calendar.timegm(dateutil.parser.parse(value).utctimetuple()))

def datetimeobj_any(value):
    """Convert timestamp string to a datetime object.

    Most timestamps strings are supported in fact this is a wrapper for the
    dateutil.parser.parse() method. This is SLOW use the other datetimeobj_*()
    functions if possible.

    Args:
        value: A timestamp string.

    Returns:
        A datetime object.
    """
    return dateutil.parser.parse(value)

_timestamp_formats = {
    "%d %b %Y %H:%M:%S"       : timestamp_d_b_Y_H_M_S,
    "%a, %d %b %Y %H:%M:%S %z": timestamp_a__d_b_Y_H_M_S_z,
    "%Y%m%d%H%M%S"            : timestamp_YmdHMS,
    "epoch"                   : timestamp_epoch,
}

def timestamp(value, fmt=None):
    """Parse a datetime to a unix timestamp.

    Uses fast custom parsing for common datetime formats or the slow dateutil
    parser for other formats. This is a trade off between ease of use and speed
    and is very useful for fast parsing of timestamp strings whose format may
    standard but varied or unknown prior to parsing.

    Common formats include:
        1 Feb 2010 12:00:00 GMT
        Mon, 1 Feb 2010 22:00:00 +1000
        20100201120000
        1383470155 (seconds since epoch)

    See the other timestamp_*() functions for more details.

    Args:
        value: A string representing a datetime.
        fmt: A timestamp format string like for time.strptime().

    Returns:
        The time in seconds since epoch as and integer for the value specified.
    """
    if fmt:
        return _timestamp_formats.get(fmt,
            lambda v: timestamp_fmt(v, fmt)
        )(value)

    l = len(value)

    if 19 <= l <= 24 and value[3] == " ":
        # '%d %b %Y %H:%M:%Sxxxx'
        try:
            return timestamp_d_b_Y_H_M_S(value)
        except (KeyError, ValueError, OverflowError):
            pass

    if 30 <= l <= 31:
        # '%a, %d %b %Y %H:%M:%S %z'
        try:
            return timestamp_a__d_b_Y_H_M_S_z(value)
        except (KeyError, ValueError, OverflowError):
            pass

    if l == 14:
        # '%Y%m%d%H%M%S'
        try:
            return timestamp_YmdHMS(value)
        except (ValueError, OverflowError):
            pass

    # epoch timestamp
    try:
        return timestamp_epoch(value)
    except ValueError:
        pass

    # slow version
    return timestamp_any(value)

_datetimeobj_formats = {
    "%d %b %Y %H:%M:%S"       : datetimeobj_d_b_Y_H_M_S,
    "%a, %d %b %Y %H:%M:%S %z": datetimeobj_a__d_b_Y_H_M_S_z,
    "%Y%m%d%H%M%S"            : datetimeobj_YmdHMS,
    "epoch"                   : datetimeobj_epoch,
}

def datetimeobj(value, fmt=None):
    """Parse a datetime to a datetime object.

    Uses fast custom parsing for common datetime formats or the slow dateutil
    parser for other formats. This is a trade off between ease of use and speed
    and is very useful for fast parsing of timestamp strings whose format may
    standard but varied or unknown prior to parsing.

    Common formats include:
        1 Feb 2010 12:00:00 GMT
        Mon, 1 Feb 2010 22:00:00 +1000
        20100201120000
        1383470155 (seconds since epoch)

    See the other datetimeobj_*() functions for more details.

    Args:
        value: A string representing a datetime.

    Returns:
        A datetime object.
    """
    if fmt:
        return _datetimeobj_formats.get(fmt,
            lambda v: datetimeobj_fmt(v, fmt)
        )(value)

    l = len(value)

    if 19 <= l <= 24 and value[3] == " ":
        # '%d %b %Y %H:%M:%Sxxxx'
        try:
            return datetimeobj_d_b_Y_H_M_S(value)
        except (KeyError, ValueError):
            pass

    if 30 <= l <= 31:
        # '%a, %d %b %Y %H:%M:%S %z'
        try:
            return datetimeobj_a__d_b_Y_H_M_S_z(value)
        except (KeyError, ValueError):
            pass

    if l == 14:
        # '%Y%m%d%H%M%S'
        try:
            return datetimeobj_YmdHMS(value)
        except ValueError:
            pass

    # epoch timestamp
    try:
        return datetimeobj_epoch(value)
    except ValueError:
        pass

    # slow version
    return datetimeobj_any(value)


# testing
if __name__ == "__main__":

    import sys
    import timeit

    log = sys.stdout.write

    times = (
        datetime.datetime.now(TZ_UTC),
        datetime.datetime.now(TZ_GMT),
        datetime.datetime.now(TZ_LOCAL),
        datetime.datetime.now(),
    )

    # check timezones
    for t in times:
        log("%s\n" % t.strftime("%Y-%m-%d %H:%M:%S %Z"))

    # TODO validate values (properly)

    # check speed
    values = (
        {
            "name": "Implemented Format",
            "time": "20130624201912",
            "fmt" : "%Y%m%d%H%M%S"
        },
        {
            "name": "Unimplemented Format",
            "time": "2013-06-24 20:19:12",
            "fmt" : "%Y-%m-%d %H:%M:%S"
        }
    )
    tests = (
        {
            "name" : "GMT timestamp (strptime version)",
            "test" : "int(calendar.timegm(datetime.datetime.strptime('%(time)s', '%(fmt)s').utctimetuple()))",
            "setup": "import calendar, datetime",
        },
        {
            "name" : "GMT timestamp (dateutil version)",
            "test" : "int(calendar.timegm(dateutil.parser.parse('%(time)s').utctimetuple()))",
            "setup": "import calendar, dateutil.parser",
        },
        {
            "name" : "GMT timestamp (fast version)",
            "test" : "timestamp('%(time)s')",
            "setup": "from __main__ import timestamp",
        },
        {
            "name" : "GMT timestamp (fast version with format hint)",
            "test" : "timestamp('%(time)s', '%(fmt)s')",
            "setup": "from __main__ import timestamp",
        },
        {
            "name" : "GMT datetime object (strptime version)",
            "test" : "datetime.datetime.strptime('%(time)s', '%(fmt)s').replace(tzinfo=TZ_GMT)",
            "setup": "import datetime; from __main__ import TZ_GMT",
        },
        {
            "name" : "GMT datetime object (dateutil version)",
            "test" : "dateutil.parser.parse('%(time)s').replace(tzinfo=TZ_GMT)",
            "setup": "import dateutil.parser; from __main__ import TZ_GMT",
        },
        {
            "name" : "GMT datetime object (fast version)",
            "test" : "datetimeobj('%(time)s')",
            "setup": "from __main__ import datetimeobj",
        },
        {
            "name" : "GMT datetime object (fast version with format hint)",
            "test" : "datetimeobj('%(time)s', '%(fmt)s')",
            "setup": "from __main__ import datetimeobj",
        }
    )
    iters = 100000
    for v in values:
        log("%(name)s (%(fmt)s)\n" % v)
        for t in tests:
            log("  %(name)-52s" % t)
            elapsed = timeit.timeit(t["test"] % v, t["setup"], number=iters)
            log("%0.3f sec (%d loops @ %0.3f usec)\n" % (
                elapsed, iters, (elapsed/iters)*1000000
            ))
