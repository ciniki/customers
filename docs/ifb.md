Individuals, Families, Businesses
---------------------------------

There is a new customer mode available to allow handling of family
accounts and business accounts simplier and more accessible. This mode
is referred to as IFB (Individuals, Families and Businesses).

Individuals - A single customer record, no relations to other accounts
Families - A bare bones customer record that contains the family name, and
    all the parents and children accounts link to this account as parent_id.
    - Parents 
        A customer record, will parent_id pointing to the family customer account.
        This customer can login and manage the family, and other accounts
        for the family.
    - Children
        A customer record that cannot change anything for the family
        or parent records. They may or may not have access to login 
        for their own information.
Businesses - A bare bones customer record that contains the business name.
    - Admins
        An employee or owner who is the admin for the company. They are
        allowed to add/remove other admins or company employees.
    - Employee
        A customer record that is not allowed to make any changes
        to the company or other employees.


Account Type IDs:
10 - Individuals
20 - Family
21 - Parent
22 - Child
30 - Business
31 - Admin
32 - Employee

Incompatibilities
-----------------
The following customer module options are not compatible with IFB flag.

- Customers, Members, Member Categories, Memberships
- Dealers, Dealer Categories
- Distributors, Distributor Categories
- Price Points, Sales Reps
- Tax Number, Tax Locations, Reward Levels
- Sales Total, Children, Customer Categories, Customer Tags
- Address Phone Numbers, Membership Seasons, Start Date, Discounts
- Single Phones, Single Email, Single Address
- Academics, Dropbox

