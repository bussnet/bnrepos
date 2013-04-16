BNRepo - Data Repositories (Local, FTP, SFTP, S3...)
=========

BNRepo is a PHP5 library based on [Gaufrette](https://github.com/KnpLabs/Gaufrette), that provides different Repositories for filesystem abstraction.

The RepositoryManager is lazy - no actions or objects before the first use


Getting Started
----------

### 1. Create Config File (Local Dirs, FTP, SFTP, S3)
```yaml
# repositories.yml
bnrepo-test:
 - type: local
 - dir: /tmp
```

### 2. Import Configuration for your Repositories
```php
  RepositoryManager::importRepositoriesFromYamlFile('repositories.yml');
```

### 3. Get the Repository from the RepositoryManager

```php
  $repo = RepositoryManager::getRepository('bnrepo-test');
```

### 4. Read/Write/Download/Upload Data/Files

```php
  $repo->write('test.txt', 'Hello World');
  echo $repo->read('test.txt') // prints Hello World
```

Documentation
-------

### Configuration

Setup your ConfigFile repositories.yml:
```yml
bnrepo-test-local:
  type: local
  dir: /tmp/bnrepo-test
  create: true

bnrepo-test-s3:
  type: s3
  aws_key: AWS_KEY
  aws_secret: AWS_SECRET
  bucket: bnrepo-test
  dir: bnrepo-test
  create: true
  use_old_version: true
  options:
    default_acl: public-read #DEFAULT is only BucketOwner can read, so everyone with the link can read

bnrepo-test-ftp:
  type: ftp
  host: HOST
  username: USERNAME
  password: PASSWORD
  dir: /bnrepo-test
  passive: false

bnrepo-test-sftp:
  type: sftp
  host: HOST
  port: PORT
  dir: /bnrepo-test
  username: USERNAME
  password: PASSWORD
```

### Load the Configuration

```php
  RepositoryManager::importRepositoriesFromYamlFile('repositories.yml');
```

### Use the Repositories


```php
  $repo = RepositoryManager::getRepository('bnrepo-test');
```

**Every Repository is an \Gaufrette\Filesystem, so check out there [docs](https://github.com/KnpLabs/Gaufrette) too.**

Running the Tests
-----------------

The tests use PHPUnit.

### Setup the vendor libraries

As some filesystem adapters use vendor libraries, you should install the vendors:

    $ cd BNRepo
    $ php composer.phar install

Implement the configurations for the repositories you want to test and remove the *-DISABLED* postfix.
Without Configuration the hole AdapterTest would skipped.

### Launch the Test Suite

    $ phpunit

Is it green?

