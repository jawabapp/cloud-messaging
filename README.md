# JawabApp CloudMessaging

## Installation

You can install the package via composer:

```bash
composer require jawabapp/cloud-messaging
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

###### web.php web route file

```php
Route::group(['prefix' => env('JAWAB_CLOUD_MESSAGING_PATH', 'jawab-notifications')], function () {
    Route::group(['prefix' => 'api'], function () {
        Route::get('countries', 'Api\Admin\CountryController@index');
        //...
    });
});
```

###### CountryController.php web route file

```php
public function countries(Request $request)
{

    $mobile_os = $request->get('os');

    return User::select(['phone_country_code'])
        ->distinct()
        ->whereNotNull('phone_country_code')
        ->where('os', $mobile_os)
        ->get()
        ->map(function ($item) {
            return [
                'value' => $item->phone_country_code,
                'text' => $item->phone_country_code,
            ];
        });
}
```

---

##### change notifilable model

###### cloud-messaging.php config file

```php
[
	'notifiable_model' => \App\Models\User::class,
]
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
