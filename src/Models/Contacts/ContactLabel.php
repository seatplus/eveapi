<?php


namespace Seatplus\Eveapi\Models\Contacts;


use Illuminate\Database\Eloquent\Model;

class ContactLabel extends Model
{
    protected $guarded = false;

    protected $with = ['contact.contactable.labels'];

    protected $appends = ['label_name'];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function getLabelNameAttribute()
    {
        return $this->contact->contactable->labels->first(fn($label) => $label->label_id === $this->label_id)->label_name ?? null;
    }
}
