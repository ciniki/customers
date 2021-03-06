Overview
========

The customers module is used to store information about customers for a
specific tenant.  

*Note* Unedited rambling, take with grain of salt...

If a person is a customer of multiple tenants in the instance,
they will have multiple records in the customers table.  This is required
so each tenant has permissions to change the customer information, such
as email address, names, and addresses.

If the customer wants to login to a online store, or to monitor 
orders, etc, they must have an account setup in the Users module.  
There will be duplicate information in the database with this design,
but it is unavoidable.  There are functions in the Customers module
and the Users module to compare information between the two tables
and determine if one contains newer information.

If the customer logs into the system and changes their information,
it will be automatically updated in both the Customers and Users tables 
via the Users.updateUserInfo function.  This function will call the 
ciniki.tenants.updateCustomerInfo to update all their records.

If the tenant owner or employee logs into the system and changes 
customer information, it will only be changed in the Customers module.  
The next time the Customer logs in, it will warn the UI that the 
information is new in the Customers module than the Users module, and
would they like to update.  This will then propagate that information
to all the users entries in the ciniki_customers table.

April 2018 **New Customer Mode Added**

Refer to [Individual/Families/Businesses](ifb.md)

