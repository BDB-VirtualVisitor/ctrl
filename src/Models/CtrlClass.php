<?php

namespace Phnxdgtl\Ctrl\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;

class CtrlClass extends Model
{

	/**
     * The attributes that are mass assignable; required for firstOrNew when scaffolding the database
     *
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * Return all properties for this class
     */
    public function ctrl_properties()
    {
        return $this->hasMany('Phnxdgtl\Ctrl\Models\CtrlProperty')->orderBy('order');
    }

    /**
     * Return all properties for this class
     */
    public function related_ctrl_properties()
    {
        return $this->hasMany('Phnxdgtl\Ctrl\Models\CtrlProperty','related_to_id','id')->orderBy('order');
    }

    // I think that the following methods should be helper functions within the controller, really...

    /**
     * Return the name of the class defined by this ctrl_class
     * @return string
     */
    public function get_class() {
        $class = "\App\Ctrl\Models\\{$this->name}";
        return $class;
    }

     /**
     * Return the name of the icon for this ctrl_class
     * @return string
     */
    public function get_icon() {
        $icon = '';
        if ($this->icon) {
            $icon = $this->icon;
        }
        else {
            $icon = 'fa-toggle-right';
        }
        if (strpos($icon,'fa') === 0) { // Identify Font Awesome icons automatically
            $icon = "fa $icon";
        }
        return $icon;
    }

    /**
     * Return the plural name of this class
     * @return string
     */
    public function get_plural() {
        $plural = '';
        if ($this->plural) {
            $plural = $this->plural;
        }
        else if ($this->singular) {
            $plural = Str::plural($this->singular);
        }
        else {
            $plural = strtolower(Str::plural($this->name));
        }
        return $plural;
    }
    /**
     * Return the singular name of this class
     * @return string
     */
    public function get_singular() {

        $singular = '';
        if ($this->singular) {
            $singular = $this->singular;
        }
        else {
            $singular = strtolower($this->name);
        }
        return $singular;

    }

    /**
     * Check whether we can $action this class; based initially on ->permissions, but should be easily extended (using a Module)
     * @param  string $action What we're trying to do (eg, 'edit' or 'import')
     * @return boolean
     */
    public function can($action) {
        $permissions = explode(',', $this->permissions);
        return in_array($action,$permissions);
    }

    /**
     * A shortcut function to quickly check whether a class is flagged $flag; saves calling in_array() etc all the time
     * @param  string $flag THe flag we want to check
     * @return boolean          Whether or not the flag is set
     */
    public function flagged($flag) {

        if (!array_key_exists('flags', $this->attributes)) return false; // 'flags' is a new field for ctrl_classes, we may not have it if the CTRL database is behind the codebase
        return (in_array($flag, explode(',',$this->flags)));
    }
}
