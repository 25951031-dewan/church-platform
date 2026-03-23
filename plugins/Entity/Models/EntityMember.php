<?php
namespace Plugins\Entity\Models;

use Illuminate\Database\Eloquent\Model;

class EntityMember extends Model
{
    protected $table = 'entity_members';

    protected $fillable = ['entity_id', 'user_id', 'role', 'status', 'invited_by'];

    public function entity()
    {
        return $this->belongsTo(ChurchEntity::class, 'entity_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
