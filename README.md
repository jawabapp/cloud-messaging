# JawabApp CloudMessaging

## Installation

You can install the package via composer:

```bash
composer require jawab/firebase-cloud-messaging
```

## Usage

###### User.php Model

```php
use JawabApp\CloudMessaging\Contracts\TargetAudience;
use JawabApp\CloudMessaging\Traits\EloquentGetTableNameTrait;
use JawabApp\CloudMessaging\Traits\HasTargetAudience;

class User extends Authenticatable implements TargetAudience
{
	use HasTargetAudience;
	use EloquentGetTableNameTrait;
	//...
}
```

implement those methods from TargetAudience interface.

```php

use Illuminate\Database\Eloquent\Builder;

class User extends Authenticatable implements TargetAudience
{
	public static function targetAudienceForPhoneNumbers(Builder $query, $phone_numbers)
    {
        //...
    }

	public static function targetAudienceForOS(Builder $query, $os)
    {
        //...
    }
}
```

---

##### Adding more filters

###### User.php Model

```php
public static function targetAudienceForCountries(Builder $query, $condition, $options, &$joins)
    {
        if ($condition === 'is_not_in') {
            $query->whereNotIn('phone_country', $options);
        } else {
            $query->whereIn('phone_country', $options);
        }
    }
```

###### cloud-messaging.php config file

```php
'filter_types' => [
	[
            'value' => 'countries',
            'label' => 'Country/Region',
            'selectLabel' => 'Countries',
            'conditions' => [
                [
                    'value' => 'is_in',
                    'label' => 'Is in',
                ],
                [
                    'value' => 'is_not_in',
                    'label' => 'Is not in',
                ]
            ]
	],
	//...
]
```

---

##### change notifilable model

###### cloud-messaging.php config file

```php
[
	'notifiable_model' => \App\Models\UserMobile::class,
]
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email trmdy@hotmail.com instead of using the issue tracker.

## Credits

- [Ahmed Magdy](https://github.com/jawab)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
