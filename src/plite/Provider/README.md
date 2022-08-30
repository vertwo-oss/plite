# Providers

These are "service" wrappers.

One example is a "File Provider", named FilepProvider.  Ha.

It abstracts the reading and writing of a file to a filesystem.

The abstraction allows various implementations, which include a local,
unix-y fileystem, as well as an AWS S3 (cloud based) blob storage facility.

## AWS Authentication

As of Aug 2022, ElasticBeanstalk + PHP + PHP SDK 3.1.128 does not play
nicely with Secrets Manager.  It has to do with the ElasticBeanstalk
Amazon Linux 2 instance somehow doing instance metadata differently,
resulting in a PHP Fatal Error.

Updating the AWS PHP SDK to 3.234.4 works.

Apparently this happened in June 2020.

See:

* https://docs.aws.amazon.com/elasticbeanstalk/latest/relnotes/release-2020-06-10-imdsv2.html
* https://stackoverflow.com/questions/59916823/how-to-use-imdsv2-in-an-elastic-beanstalk-environment
* https://github.com/fluent/fluent-bit/issues/2840

None of which solved my problem, but gave hints.

Putting that together with this:

> "AWS SDK – If your application uses an AWS SDK, make sure you use an the latest version of the SDK. The AWS SDKs make IMDS calls, and newer SDK versions use IMDSv2 whenever possible. If you ever disable IMDSv1, or if your application uses an old SDK version, IMDS calls might fail."

Found here:

* https://docs.aws.amazon.com/elasticbeanstalk/latest/dg/environments-cfg-ec2-imds.html

Gave me the hint that using a newer version of the PHP SDK might solve the issue.

And I found this stuff, eventually, with this search:

> "elasticbeanstalk using imdsv2"

* https://duckduckgo.com/?q=elasticbeanstalk+using+imdsv2&t=osx&ia=web

