<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSecurityEvents extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('security_events', function(Blueprint $table)
		{
			$table->increments('id');

			$table->text('description');
			$table->integer('result');

			$table->integer('characterID');
			$table->text('updated_by');
			$table->integer('alertID');
			$table->text('notes');
			$table->char('hash',32);

			// Indexes
			$table->index('characterID');

			$table->timestamps();
		});
    Schema::create('security_keywords', function(Blueprint $table)
    {
  		$table->increments('id');

  		$table->string('keyword',255);
  		$table->char('type',4);

  		// Indexes
  		$table->index('type');

      $table->timestamps();
    });
    Schema::create('security_alerts', function(Blueprint $table)
    {
      $table->increments('alertID');

      $table->string('alertName',255);

      // Indexes
    });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('security_events');
    Schema::dropIfExists('security_keywords');
    Schema::dropIfExists('security_alerts');
	}

}
