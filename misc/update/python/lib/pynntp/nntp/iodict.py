#!/usr/bin/python
"""
Case-insentitive ordered dictionary (useful for headers).
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

from collections import OrderedDict

def _lower(v):
    """assumes that classes that inherit list, tuple or dict have a constructor
    that is compatible with those base classes. If you are using classes that
    don't satisfy this requirement you can subclass them and add a lower()
    method for the class"""
    if hasattr(v, "lower"):
        return v.lower()
    if isinstance(v, (list, tuple)):
        return v.__class__(_lower(x) for x in v)
    if isinstance(v, dict):
        return v.__class__(_lower(v.items()))
    return v


# NOTE: This class makes assumptions about the OrderedDict class that it
# inherits from -- this a a bad idea. If The OrderedDict Class were to change
# behaviour it could break this class.
class IODict(OrderedDict):
    """Case in-sensitive ordered dictionary.
    >>> iod = IODict([('ABC', 1), ('DeF', 'A'), (('gHi', 'jkl', 20), 'b')])
    >>> iod
    IODict([('ABC', 1), ('DeF', 'A'), (('gHi', 'jkl', 20), 'b')])
    >>> iod['ABC'], iod['abc'], iod['aBc']
    (1, 1, 1)
    >>> iod['DeF'], iod['def'], iod['dEf']
    ('A', 'A', 'A')
    >>> iod[('gHi', 'jkl', 20)], iod[('ghi', 'jKL', 20)]
    ('b', 'b')
    >>> iod == {"aBc": 1, "deF": 'A', ('Ghi', 'JKL', 20): 'b'}
    True
    >>> iod.popitem()
    (('gHi', 'jkl', 20), 'b')
    """

    def __init__(self, *args, **kwds):
        self.__map = {}
        OrderedDict.__init__(self, *args, **kwds)

    def __setitem__(self, key, *args, **kwds):
        l = _lower(key)
        OrderedDict.__setitem__(self, l, *args, **kwds)
        self.__map[l] = key

    def __getitem__(self, key, *args, **kwds):
        l = _lower(key)
        return OrderedDict.__getitem__(self, l, *args, **kwds)

    def __delitem__(self, key, *args, **kwds):
        l = _lower(key)
        OrderedDict.__delitem__(self, l, *args, **kwds)
        del self.__map[l]

    def __contains__(self, key):
        l = _lower(key)
        return OrderedDict.__contains__(self, l)

    def __iter__(self):
        for k in OrderedDict.__iter__(self):
            yield self.__map[k]

    def __reversed__(self):
        for k in OrderedDict.__reversed__(self):
            yield self.__map[k]

    def clear(self):
        OrderedDict.clear(self)
        self.__map.clear()

    def __eq__(self, other):
        """assumes that classes that inherit dict have a constructor that is
        compatible with the dict class."""
        if len(self) != len(other) or not isinstance(other, dict):
            return False
        so = OrderedDict(zip(_lower(self.keys()), self.values()))
        oo = other.__class__(zip(_lower(other.keys()), other.values()))
        return so == oo

if __name__ == "__main__":
    import doctest
    doctest.testmod()
