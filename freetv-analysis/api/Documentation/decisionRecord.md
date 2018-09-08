# High Level Decision Record
The purpose of this file is to record high level decisions about the application and though process that went into those decisions. When a decision is made to change the code in a way that effects the architecture of the system or a new feature is added the rationale for taking this direction (or not taking some direction).

## Instructions
* Add a new entry to the top of the next section with the following format
  * The date and Title (h3)
  * A short description of the change
  * A short rationale of why the change is being made
  * List files or classes effected
  * Your name(s)

## Example
### [2017-04-15] Added extra integration with some service
* **Description** We added a new integration with some service to validate data coming through from input on its way to the db
* **Rationale** We made this change because invalid data were getting into the databse, there was limited time to implement so we decided to use some service to do the work for us.
* **Files Affected** /project/input.js is the main integration point
* **Author/s** Jack <jack@gmail.com> and Jill <jill@gmail.com>


# FreeTV API Decision Record

### [2017-10-12] Document sending on production conditionally base64 encodes the files
* **Description** \
On the production environment, document retrieval from the API conditionally returns the base64 encoded file
* **Rationale** \
IIS was not allowing for large MPEG/script files to be base64 encoded and sent by the API, so this was a workaround since agency files are on the same server on the system. Since the files are most usually on different computers for linux developers, the config flag `alwaysEncode` can be added to encode all files sent by the API. All files that are internally uploaded/system generated are still encoded
* **Files Affected** \
 /src/Services/documentUpload.php\
  /src/Models/Report.php
* **Author/s** \
Jeremy Paul <jeremy.paul@4mation.com.au>
