<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Icon;
use PhpParser\Node\Stmt\Foreach_;

class ImportIconSet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iconset:import {mode} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import icon set';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $argument = strtolower($this->argument('mode'));

        switch ($argument) {
            case 'show':
                $this->showAllIcons();
                break;
            case 'help':
                $this->help();
                break;
            case 'addkeywords':
                $this->addkeywords();
                break;
            case 'addall':
                $this->addallkeywords();
                break;

            default:
                # code...
                break;
        }

        // echo $this->argument('package');
        // $this->info('Installing BlogPackage...');
        // $this->info('Publishing configuration...');

        // $choice = $this->choice("Choose person", [
        //     1    =>    'Dave',
        //     2    =>    'John',
        //     3    =>    'Roy'
        // ]);

        // echo $choice;

        // $name = $this->ask('What is your name?');

        // echo $name;
        // if ($this->confirm('Do you wish to continue?')) {

        // }

    }

    protected function showAllIcons(){
        $headers = [ 'id', 'icon_set_id', 'name', 'keywords' ];
        $icons = Icon::all(['id', 'icon_set_id', 'name'])->toArray();
         $this->table($headers, $icons);

        // $iconarray = array();
        // foreach ($icons as $key) {
        //     if(isset($key->keywords)){
        //     foreach ($key->keywords as $key => $value) {
        //         $keyword = "";
        //         for($i = 0;$i<count($value);$i++) {
        //             $keyword.=$value[$i].",";
        //         }
        //         array_push($iconarray,$key->id,)
        //     }
        //     }

        // }
        return 0;
    }

    protected function help(){
        echo "show  - will show all Icons in Database \n";
        echo "help  - shows all options\n";
        echo "addkeywords  - add Keywords to unique Icons\n";
        echo "addall  - add Keywords to all Icons\n";
    }

    protected function addkeywords(){
        $id = $this->ask('What is the Icon id?');
        $keywords = $this->ask('What keywords');

        Icon::all('id',$id)->update([
            'keywords'=>'{"Keywords":['.$keywords.']}',
        ]);
    }

    protected function addallkeywords(){
        $keywords = $this->ask('What keywords');
        foreach(Icon::all() as $element){
            Icon::where('id',$element->id)->update([
                'keywords'=>'{"Keywords":['.$keywords.']}',
            ]);
        }
    }
}
