# Changelog

## 0.3.003 - 2019-12-06

- Fix an issue with exporting stanzas that have multiple IncludeFiles
  - Some stanzas (such as ClinicalKey and Embase) have multiple IncludeFile definitions for OCLC hosted files;
    these are now correctly handled
- This update also addresses an issue where empty lines were being removed from stanzas

## 0.3.002 - 2019-11-14

- Added support for GitHub authentication
  - Unauthenticated requests to the GitHub API are limited to 60 per hour, which makes using GitHub as the datastore
    somewhat impractical
  - Authenticated GitHub API requests are limited to 5000 per hour

## 0.3.001 - 2019-10-21

- Changed version numbering scheme
- Add rules management
  - Rules allow the original stanza to be manipulated/modified before being placed in the EZproxy
    file. An example would be OCLC stanzas with placeholders (like usernames or passwords)
    that need to be changed before that stanza can be used.
  - Currently supported rules are Prepend, Append, and Replace

## 0.2.beta.012 - 2019-10-10

- Fix an error that prevented the active/inactive state of stanzas from being saved correctly

## 0.2.beta.011 - 2019-10-10

- Added GitHub link to stanza display

## 0.2.beta.010 - 2019-10-09

- Minor UI change to editor display

## 0.2.beta.009 - 2019-10-09

- Recent updates to the EZproxy stanza repository
  ([ezproxy-stanzas](https://github.com/usask-library/ezproxy-stanzas)) are now shown on the
  main page

## 0.2.beta.008 - 2019-10-07

- Allow GitHub to function as the stanza datastore

## 0.2.beta.006 - 2019-10-04

- Update install instructions
- Improve EZproxy import and export procedures
  - improved validation of imported EZproxy files
  - improved display of error/warning messages generated as part of the import
  - improved recognition of inactive entries in the import file
  - modified the format of the export file
  - improved the import process for files previously exported by Costanza

## 0.2.beta.002 - 2019-09-27

- Update lodash to 4.17.15

## 0.2.beta.001 - 2019-09-27

- Update eslint-utils to 1.4.2
