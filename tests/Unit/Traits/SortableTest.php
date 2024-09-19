<?php
namespace Finxp\Flexcube\Tests\Unit\Traits;

use Finxp\Flexcube\Models\FCTransactions;
use Finxp\Flexcube\Tests\TestCase;
use Illuminate\Http\Request;

class SoratbleTest extends TestCase
{

    /** @test */
    public function itShouldSortId()
    {
        
        $fcnew = FCTransactions::factory()->count(5)->create();

        $fcTransaction = FCTransactions::sortable();

        $this->assertEquals($fcnew->count(), $fcTransaction->count());

    }

    /** @test */
    public function itShouldSortRelation()
    {
        
        $fcnew = FCTransactions::factory()->count(5)->create();
        
        $param = [
            'sort' => 'parent.uuid',
            'direction' => 'desc'
        ];
            
        $fcTransaction = FCTransactions::sortable($param);

        $this->assertEquals($fcnew->count(), $fcTransaction->count());
    }

    /** @test */
    public function itShouldSortException()
    {
        
        $fcnew = FCTransactions::factory()->count(5)->create();
        
        $param = [
            'sort' => 'test',
            'direction' => 'asc'
        ];
         
        $fcTransaction = FCTransactions::sortable($param);

        $this->assertEquals($fcnew->count(), $fcTransaction->count());
    }

}
