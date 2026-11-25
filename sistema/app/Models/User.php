<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Atributos protegidos contra asignación masiva.
     *
     * @var list<string>
     */
    protected $guarded = [
        'id',
        'remember_token',
        'email_verified_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Atributos excluidos al convertir el modelo a arreglo o JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'user_id', 'id');
    }

    /**
     * Definición de conversiones de tipo para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
