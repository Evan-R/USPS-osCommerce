## USPS-osCommerce
- a USPS module for osCommerce 2.2/2.3

## Kindly Sponsored By:
- Til Luchau (http://advanced-trainings.com)
- Anonymous Sponsor #1
- Aedea Innovations Inc.
- Evanay Solutions Ltd.

## Installation
- **always backup files and database before performing installation or upgrade operations**
- uninstall current USPS module if installed (and it is a good idea to take note of the pre-existing values)
- copy files over to their corresponding paths
- it may be necessary in `admin/modules.php` to add some code to support arrays, around line 45: look for
```php
  if (tep_not_null($action)) {
    switch ($action) {
      case 'save':
```

..and change to this: 

```php
  if (tep_not_null($action)) {
    switch ($action) {
      case 'save':
        $module_name = '';
      	// detect custom modules..
      	if( array_key_exists('MODULE_SHIPPING_USPS_STATUS', $HTTP_POST_VARS['configuration']) ){	
      		$module_name = 'usps';
      	}
```

then, shortly after, look for
```php
        while (list($key, $value) = each($HTTP_POST_VARS['configuration'])) {
          tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = '" . $value . "' where configuration_key = '" . $key . "'");
        }
```

..and change to this:
```php
        while (list($key, $value) = each($HTTP_POST_VARS['configuration'])) {
        	switch( $module_name ) {
        		case 'usps':
        			/*
        			 * TODO: review
        			* USPS Methods
        			*/
        			if( is_array( $value ) ){
        				$value = implode( ", ", $value);
        				$value = ereg_replace (", --none--", "", $value);
        			}
        			/*
        			 * /end
        			*/
        			break;
        	}
          tep_db_query("update " . TABLE_CONFIGURATION . " set configuration_value = '" . $value . "' where configuration_key = '" . $key . "'");
        }
```

## Authors:

### 5.x, 7.x
- Brad Waite
- Fritz Clapp
- Greg Deeth
- Kevin L Shelton
- Evan Roberts (evan.aedea@gmail.com)
- John Ny


## Wishlist: 
- code cleanup
- field for (tm) / (r) symbols so that they can easily be updated if USPS decides to change the format again
- refactoring (code is rather scary right now..)

