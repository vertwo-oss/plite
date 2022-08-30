# Providers

These are "service" wrappers.

One example is a "File Provider", named FilepProvider.  Ha.

It abstracts the reading and writing of a file to a filesystem.

The abstraction allows various implementations, which include a local,
unix-y fileystem, as well as an AWS S3 (cloud based) blob storage facility.
