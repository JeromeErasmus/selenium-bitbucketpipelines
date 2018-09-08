<?php

namespace App\Models;

use App\Models\EloquentModel;
use Elf\Exception\NotFoundException;

/**
 * @author Michael
 */
class CreditCardScheme extends EloquentModel
{
    protected $primaryKey = "id";
    protected $table = 'credit_card_types';


    protected $fieldMap = [
        'id' => [
            'name' => 'id',
        ],
        'name' => [
            'name' => 'name',
            'rules' => 'required|string',
        ],
    ];



}