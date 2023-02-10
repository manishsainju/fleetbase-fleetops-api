<?php

namespace Fleetbase\Models;

use Illuminate\Notifications\Notifiable;
use Fleetbase\Scopes\VendorScope;
use Fleetbase\Casts\Json;
use Fleetbase\Traits\HasUuid;
use Fleetbase\Traits\HasPublicId;
use Fleetbase\Traits\TracksApiCredential;
use Fleetbase\Traits\HasApiModelBehavior;
use Fleetbase\Traits\HasInternalId;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\SlugOptions;
use Spatie\Sluggable\HasSlug;
use Fleetbase\Traits\Searchable;

class Vendor extends Model
{
    use HasUuid, HasPublicId, HasApiModelBehavior, HasInternalId, TracksApiCredential, Searchable, HasSlug, LogsActivity, Notifiable;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vendors';

    /**
     * The type of public Id to generate
     *
     * @var string
     */
    protected $publicIdType = 'vendor';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        '_key',
        'internal_id',
        'company_uuid',
        'logo_uuid',
        'type_uuid',
        'connect_company_uuid',
        'business_id',
        'name',
        'email',
        'website_url',
        'meta',
        'callbacks',
        'phone',
        'place_uuid',
        'country',
        'status',
        'type',
        'slug',
    ];

    /**
     * These attributes that can be queried
     *
     * @var array
     */
    protected $searchableColumns = ['name', 'email', 'business_id', 'company.name'];

    /**
     * Dynamic attributes that are appended to object
     *
     * @var array
     */
    protected $appends = ['address', 'address_street', 'logo_url'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'callbacks' => Json::class,
        'meta' => Json::class
    ];

    /**
     * Relationships to auto load with driver
     *
     * @var array
     */
    protected $with = ['place'];

    /**
     * Properties which activity needs to be logged
     *
     * @var array
     */
    protected static $logAttributes = ['name', 'email', 'website_url', 'phone', 'country', 'status', 'type', 'logo_uuid', 'company_uuid'];

    /**
     * Do not log empty changed
     *
     * @var boolean
     */
    protected static $submitEmptyLogs = false;

    /**
     * We only want to log changed attributes
     *
     * @var boolean
     */
    protected static $logOnlyDirty = true;

    /**
     * The name of the subject to log
     *
     * @var string
     */
    protected static $logName = 'vendor';

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new VendorScope());
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['place'];

    /**
     * The place of the vendor
     */
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * The fleetbase company instance the vendor represents
     */
    public function connectCompany()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * The company which owns this vendor record.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function logo()
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the vendor logo url.
     * 
     * @return string
     */
    public function getLogoUrlAttribute()
    {
        return static::attributeFromCache($this, 'logo.s3url', 'https://s3.ap-southeast-1.amazonaws.com/flb-assets/static/no-avatar.png');
    }

    /**
     * Returns the vendors place address
     * 
     * @return string
     */
    public function getAddressAttribute()
    {
        return static::attributeFromCache($this, 'place.address_html');
    }

    /**
     * Returns the vendors place address
     * 
     * @return string
     */
    public function getAddressStreetAttribute()
    {
        return static::attributeFromCache($this, 'place.street1');
    }

    /**
     * Notify vendor using this column.
     *
     * @return mixed|string
     */
    public function routeNotificationForTwilio()
    {
        return $this->phone;
    }
}
