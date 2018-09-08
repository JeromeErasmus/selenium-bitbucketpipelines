# FREETV API

## Overview

This API allows authenticated access points, CAD users and AgencyUserEmails to interact with FREETV feature set

There is a slack chat where you can interact with the current development team : https://freetvcadrebuild.slack.com/

## Setup

### Production and testing setup
Production and testing configs are located [here](https://bitbucket.org/4mationtechnologies/freetv-api/downloads/)

Update those as necessary when developing

### After cloning the repo
Local setup instructions are found [here](https://4mation.atlassian.net/wiki/display/FREET/FreeTV+Box+Local+Dev+Setup)

MAKE SURE YOU CREATE THE uploadRoot path specified in your config yml file

## Example requests

These can be found in Postman Cloud, refer to the current tech lead

## Scheduled Tasks

There are several scheduled tasks that require to be run

### TVC Extract
#### Long Term
In the long term, this should be converted to use /Services/Automation.php, which will allow us to centralise the task running, and allow finer control over execution times

#### Current
At the moment, tasks are run via the console.php script by providing the appropriate php executions
 ie php /console.php TvcExtract --ENVIRONMENT="development"

This task is set to run every hour at XX:45 and is called TvcExtract on the current UAT server

