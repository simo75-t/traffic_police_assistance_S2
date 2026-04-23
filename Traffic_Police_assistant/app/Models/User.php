<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use  HasFactory, Notifiable , HasApiTokens , SoftDeletes ;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role' , 
        'profile_image' , 
        'is_active', 
        'fcm_token',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function violation(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function assignedReports(): HasMany
    {
        return $this->hasMany(CitizenReport::class, 'assigned_officer_id');
    }

    public function reportAssignments(): HasMany
    {
        return $this->hasMany(ReportAssignment::class, 'officer_id');
    }

    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'uploaded_by');
    }

    public function liveLocation(): HasOne
    {
        return $this->hasOne(OfficerLiveLocation::class, 'officer_id');
    }

    public function violationsReported(): HasMany
    {
        return $this->hasMany(Violation::class);
    }
}
