from setuptools import setup

setup(
    name='pynntp',
    version='0.8.4',
    description='NNTP Library (including compressed headers)',
    author='Byron Platt',
    author_email='byron.platt@gmail.com',
    license='GPL3',
    url='https://github.com/greenbender/pynntp',
    packages=['nntp'],
    install_requires=['dateutils'],
)
