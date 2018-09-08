# Scheduled Tasks in Windows

## How to Add New Tasks and Find Old ones
### Existing
Refer to the folder

```
src/Commands/
```
This contains all existing shedulable tasks, they run utilising the framework specific console.php command

eg 
```
php /console.php TvcExtract --ENVIRONMENT="development"
```
Viable environment configs can be found in 

```
src/Config
```
### New

The class must be made up of at the bare minimum the following

```php
namespace App\Commands;
use Elf\Core\Module;
```

And must follow the .*Command naming convention

## Creating a Schedule
In Windows
The following instructions should give you an idea of where and how to create a scheduled task
```
Start>Task Scheduler>Create Task>
```
### General
Here you should name the Task appropriately, currently the naming scheme is freetv-*TaskToBeRun*

Please note that on the production server(and maybe on the development server too, if required) you should set this task to:
```
Run whether user is logged on or not
```
### Trigger
Set the Trigger appropriately:
* this can be done per hour by selecting Daily and changing the options lower on the screen
* Weekly and Monthly are also available but are currently unused
### Actions
If you are utilising the console.php commands, the following are relevant
#### Program/Script
Setting this to php, will not work as expected on Windows, as the PATH cannot be assumed to be set
Instead utilise the full executable path name
ie
```
C:/Program Files (x86)/PHP/php.exe
```
#### Add Arguments
Add the arguments that you will be passing to php.exe
```
FullDeploymentPath/console.php TvcExtract --ENVIRONMENT="development"
```
