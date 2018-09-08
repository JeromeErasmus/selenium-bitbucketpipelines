# Emails

# So you want to send an email?

Emails inside the FreeTV API are controlled by the mail service, located at 

```
    src/Services/Mail.php
```

Invoking the service takes the following form

```
    $this->mail = $this->app->service('Mail');
    $this->mailConfig = $this->app->config->get('mail');
    $this->fromAddr = $this->mailConfig['fromAddr'];
    $this->fromName = $this->mailConfig['fromName'];
    $this->notificationConfig = $this->app->config->get('notifications');
    $this->app->service('eloquent')->getCapsule();
```

The mailing service can also be utilised by extending the notifications service

```
namespace App\Services;
class newClass extends Notifications
```

The email templates are located in the following folder, with the default configuration it can be controlled via the configuration files

```
    src/Views/EmailTemplates
```

# Loading Templates

The following example for loading a template with the constructed array

```
$html = $mail->loadHtml('awaitingagencyfeedback.email', ['firstname' => $contact['name'], 'jobId' => $jobId]);
```

# Substituting values

So you've created the template and need to replace variables inside the template

The following function body will parse all the variable names

```
/**
* @param $templateHtml
* @param $variables
* @return mixed
*
* parse all {%variableName%}'s and replace with corresponding variableName in $variables array
*
*/
private function parseTemplate($html, $variables = array())
{
   if(preg_match_all('/{%(.*?)%}/', $html, $arr) > 0) {
       foreach($arr[1] as $replace) {      //go through all variables in the template
           if (isset($variables[$replace])) {
               $html = str_replace("{%$replace%}", $variables[$replace], $html); //if substitution exists, replace
           } else {
               $html = str_replace("{%$replace%}", '', $html);      //else get rid of the {%xxxx%} things
           }
       }
   }
   return $html;
}
```
# Sending the email

```
try {
    $mail->sendHtmlMessage([$contact['email'] => $contact['name']], [$this->fromAddr => $this->fromName],  'Awaiting Agency Feedback', $html );
} catch (\Exception $e) {
    // do nothing as we don't want to fail on unsent mails
}
```

# Command Line Execution
```
php AsyncScript.php AsyncNotificationSendout --ENVIRONMENT=staging --notificationType="agencyComment" --commentId="414" --replyType="1"
```