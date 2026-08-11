<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('constancias_numero_oficial_plantillas', function (Blueprint $table) {
            $table->id();
            
            $table->string('nombre_version')->unique();
            
            $table->text('cuerpo');

            $table->boolean('editable')
                  ->default(true);

            $table->boolean('activa')
                  ->default(true)
                  ->index();

            $table->timestamps();
            
            // Opcional: SoftDeletes si quieres un historial de machotes borrados
            // $table->softDeletes(); 
        });

        DB::table('constancias_numero_oficial_plantillas')->insert([
            'nombre_version' => 'Plantilla #1',
            'editable' => true,
            'activa' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'cuerpo' => '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #000; padding: 20px;">
                    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                        <tr>
                            <td style="width: 20%; text-align: left;">[LOGO_MUNICIPIO]</td>
                            <td style="width: 60%; text-align: center;">
                                <h2 style="margin: 0; font-size: 16px; text-transform: uppercase;">Dirección de Planeación y Desarrollo Urbano</h2>
                                <h3 style="margin: 5px 0 0 0; font-size: 14px;">Constancia de Número Oficial</h3>
                            </td>
                            <td style="width: 20%; text-align: right;">[LOGO_ESTADO]</td>
                        </tr>
                    </table>

                    <div style="text-align: right; margin-bottom: 30px; font-size: 12px;">
                        <strong>Oficio No:</strong> {{ numero_oficio }}<br>
                        <strong>Expediente:</strong> {{ expediente }}<br>
                        <strong>Fecha de Emisión:</strong> {{ fecha_emision }}
                    </div>

                    <div style="margin-bottom: 25px; font-size: 13px;">
                        <strong>{{ nombre_destinatario }}</strong><br>
                        {{ caracter_destinatario }}<br>
                        P R E S E N T E.
                    </div>

                    <p style="text-align: justify; font-size: 13px;">
                        En atención a su solicitud y derivado de la revisión técnica efectuada al predio de su propiedad, 
                        identificado con la <strong>Clave Catastral {{ clave_catastral }}</strong>, ubicado en 
                        <strong>{{ direccion_completa }}</strong> de esta ciudad; se tiene a bien asignar el siguiente:
                    </p>

                    <div style="margin: 40px auto; width: 85%; border: 3px double #000; padding: 25px; text-align: center;">
                        <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px;">
                            Número Oficial Asignado:
                        </div>
                        <div style="font-size: 32px; font-weight: 900; margin-bottom: 5px;">
                            {{ numero_asignado }}
                        </div>
                        <div style="font-size: 14px; font-weight: bold; font-style: italic; text-transform: uppercase; border-top: 1px solid #ccc; padding-top: 8px; display: inline-block; min-width: 200px;">
                            ({{ numero_letra }})
                        </div>
                    </div>

                    <p style="text-align: justify; font-size: 13px; margin-bottom: 50px;">
                        Lo anterior se hace de su conocimiento para los fines legales y administrativos a que haya lugar. 
                        Esta constancia no acredita propiedad y tiene vigencia permanente mientras no existan modificaciones físicas al predio.
                    </p>

                    <table style="width: 100%; margin-top: 80px;">
                        <tr>
                            <td style="text-align: center;">
                                <div style="width: 250px; border-top: 1px solid #000; margin: 0 auto 5px auto;"></div>
                                <strong style="font-size: 12px; text-transform: uppercase;">Ing. Nombre del Director</strong><br>
                                <span style="font-size: 11px;">Director de Planeación y Desarrollo Urbano</span>
                            </td>
                        </tr>
                    </table>
                </div>'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('constancias_numero_oficial_plantillas');
    }
};