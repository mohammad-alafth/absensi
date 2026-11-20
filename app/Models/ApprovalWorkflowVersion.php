<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflowVersion extends Model
{
    protected $table = 'approval_workflow_versions';

    protected $guarded = [];

    protected $casts = ['version' => 'integer'];
}
