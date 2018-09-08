# So you want to add a new template?

You'll need the following
 * Which system can access it
 * To know what variables can be manipulated
 * The keys that consumers will pass you in $htmlVariables
 
# JSON Example

Below is an example POST request that could be received.

Note the distinct lack of the field for "templateType", at point of writing, this field will default to OAS as this is the only service utilising it

 
``` 
{
    "templateName": "123.email",
    "htmlVariables": [{
        "key1": "val",
        "key2": "val",
        "key3": "val"
    }],
    "to": "email@email.com",
    "subject": "subject text"
}
```

# This isn't working for me

If crucial variables have been left out, template name, to, or subject, the endpoint will fail

If you are trying to access an email template that isn't available for your system

ie. you have not provided an templateType and there are no OAS email templates available with the provided name

If the particular environment is missing the email configuration block
```
mail:
  enabled: true
  protocol: smtp
  authentication: true
  templatePath: "E:\\api\\src\\Views\\EmailTemplates"
  fromAddr: "DoNotReply@freetv.com.au"
```

# So you're trying to add a template
There are some templates that will be suffixed and some that will not be

The templates that end with email.tpl.php are system emails that are utilised by the CAD notification system

Any templates that end with email.oas.tpl.php are OAS accessible templates that are utilised by OAS
