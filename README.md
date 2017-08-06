* Change Log

27.4.17 - v1.1.6
Change authentication mechanism from using Zurmo CRM to using secured active directory server hosted in Amazon cloud.
This solves the issue of creating a dynamic, flexible and easy to manage user & permission system without creating any additional security issues since the Active Directory is only exposed to the data warehouse application ( all further validation is handled via API request )