# Action Scheduler Custom Tables [![Build Status](https://travis-ci.org/Prospress/action-scheduler-custom-tables.png?branch=master)](https://travis-ci.org/Prospress/action-scheduler-custom-tables) [![codecov](https://codecov.io/gh/Prospress/action-scheduler-custom-tables/branch/master/graph/badge.svg)](https://codecov.io/gh/Prospress/action-scheduler-custom-tables)

Prototype of improved scalability for the Action Scheduler library using custom tables.

**This plugin is beta software. Use only on staging and test sites for now.**

## Overview

Action Scheduler uses WordPress posts to store scheduled actions in the existing database tables. This plugin hooks into Action Scheduler to set up separate tables as a datastore, improving performance and scalability.

## Migration

### WP CLI

To migrate actions in bulk using WP-CLI, run the following command:

```
wp action-scheduler custom-tables migrate [--batch=<batch>] [--dry-run]
```

