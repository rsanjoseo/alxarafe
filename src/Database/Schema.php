<?php
/**
 * Copyright (C) 2022-2023  Rafael San José Tovar   <info@rsanjoseo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Alxarafe\Database;

use Alxarafe\Core\Singletons\Config;
use Alxarafe\Core\Singletons\Debug;
use Alxarafe\Core\Singletons\FlashMessages;
use Alxarafe\Core\Singletons\Translator;
use DebugBar\DebugBarException;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Schema
 *
 * La clase abstracta Schema, define un esquema de base de datos teórico al que
 * se traduce la base de datos real y viceversa, de manera que el código sea
 * en la medida de lo posible, no dependiente de la base de datos real.
 *
 * TODO: ¿La información cacheada se procesa en YamlSchema o no merece la pena?
 *
 * @author  Rafael San José Tovar <info@rsanjoseo.com>
 * @version 2023.0101
 *
 * @package Alxarafe\Database
 */
class Schema
{
    /**
     * Tipo entero. Número sin decimales.
     */
    public const TYPE_INTEGER = 'integer';

    /**
     * Tipo real o coma flotante. Número con decimales. Puede dar problema con redondeos.
     */
    public const TYPE_FLOAT = 'float';

    /**
     * Tipo numérico de coma fija. Número con N decimales y precisión absoluta.
     * Es igual que un integer, pero se asume que un número determinado de dígitos son decimales.
     */
    public const TYPE_DECIMAL = 'decimal';

    /**
     * Tipo cadena de texto
     */
    public const TYPE_STRING = 'string';

    /**
     * Tipo bloque de texto
     */
    public const TYPE_TEXT = 'text';

    /**
     * Tipo fecha
     */
    public const TYPE_DATE = 'date';

    /**
     * Tipo hora
     */
    public const TYPE_TIME = 'time';

    /**
     * Tipo fecha + hora.
     * TODO: Hay que revisar el tema de la zona horaria.
     *       De lógica, siempre se debe de almacenar como UTC y convertir al guardar y leer.
     */
    public const TYPE_DATETIME = 'datetime';

    /**
     * Tipo lógico: TRUE o FALSE.
     */
    public const TYPE_BOOLEAN = 'bool';

    /**
     * Retorno de carro y salto de línea
     */
    const CRLF = "\r\n";

    /**
     * Contiene la definición ampliada de la estructura de la base de datos.
     *
     * @var array
     */
    public static array $bbddStructure;

    public static function checkDatabaseStructure()
    {
        foreach (YamlSchema::getTables() as $key => $table) {
            if (!file_exists($table)) {
                Debug::message('No existe la tabla ' . $table);
            }
            dump("Verificando la tabla $key, definida en $table.");
            if (!static::checkStructure($key, $table)) {
                FlashMessages::setError('Error al comprobar la estructura de la tabla ' . $table);
            }
        }
    }

    /**
     * Return true if $tableName exists in database
     *
     * @param string $tableName
     *
     * @return bool
     * @throws DebugBarException
     */
    public static function tableExists($tableName): bool
    {
        $tableNameWithPrefix = Config::$dbPrefix . $tableName;
        $dbName = Config::$dbName;
        $sql = "SELECT COUNT(*) AS Total FROM information_schema.tables WHERE table_schema = '{$dbName}' AND table_name='{$tableNameWithPrefix}'";

        $data = Engine::select($sql);
        $result = reset($data);

        return $result['Total'] === '1';
    }

    private static function getFieldsAndIndexes($tableName, $path): array
    {
        $data = Yaml::parseFile($path);

        dump([$path => $data]);

        $result = [];
        foreach ($data['fields'] ?? [] as $key => $datum) {
            $datum['key'] = $key;
            $result['fields'][$key]['db'] = DB::normalizeFromYaml($datum);
            $result['fields'][$key]['info'] = Schema::normalize($datum);
            if ($result['fields'][$key]['type'] === 'autoincrement') {
                // TODO: Ver cómo tendría que ser la primary key
                $result['indexes']['primary'] = $key;
            }
        }
        foreach ($data['indexes'] ?? [] as $key => $datum) {
            $datum['key'] = $key;
            $result['indexes'][$key] = $datum;
        }

        /*
        Igual conviene crear una clase:
        - DBSchema (con los datos de la base de datos real)
        - DefinedSchema (con los datos definidos)
        y que Schema cree o adapte según los datos de ambas. Que cada una lleve lo suyo

        Que el resultado se guarde en el yaml y que se encargue de realizar las conversines
    oportunas siempre que no suponga una pérdida de datos.
        */

        return $result;
    }

    private static function getFields($tableName): array
    {
        $yamlSourceFilename = self::$tables[$tableName];
        if (!file_exists($yamlSourceFilename)) {
            dump('No existe el archivo ' . $yamlSourceFilename);
        }

        $data = Yaml::parseFile($yamlSourceFilename);

        $result = [];
        foreach ($data as $key => $datum) {
            $datum['key'] = $key;
            $result[$key] = Schema::normalize($datum);
        }

        /*
        Igual conviene crear una clase:
        - DBSchema (con los datos de la base de datos real)
        - DefinedSchema (con los datos definidos)
        y que Schema cree o adapte según los datos de ambas. Que cada una lleve lo suyo

        Que el resultado se guarde en el yaml y que se encargue de realizar las conversines
    oportunas siempre que no suponga una pérdida de datos.
        */

        return $result;
    }

    private static function getIndexes($tableName): array
    {
        $result = [];
        return $result;
    }

    private static function getRelated($tableName): array
    {
        $result = [];
        return $result;
    }

    private static function getSeed($tableName): array
    {
        $result = [];
        return $result;
    }

    private static function checkTable(string $tableName, string $path, bool $create = true): array
    {
        $yaml = Yaml::parseFile($path);
        $fields = $yaml['fields'] ?? [];

        $data = [];
        foreach ($fields as $key => $field) {
            $field['key'] = $key;
            $schema = DB::yamlFieldToSchema($field);
            $data[$key]['db'] = DB::yamlFieldToDb($schema);
            $data[$key]['schema'] = $schema;
        }

        $indexes = $yaml['indexes'] ?? [];

        return [
            'fields' => $data,
            'indexes' => $indexes,
        ];
    }

    /**
     * Comprueba la estructura de la tabla y la crea si no existe y así se solicita.
     * Si los datos de la estructura no están en la caché, los regenera y almacena.
     * Al regenerar los datos para la caché, también realiza una verificación de
     * la estructura por si hay cambios que aplicar en la misma.
     *
     * TODO: Es mejor que haya un checkStructure que genere TODAS las tablas e índices
     * Ese checkstructure se debe de generar tras limpiar caché.
     * La caché deberá de limpiarse cada vez que se active o desactive un módulo.
     * El último paso de la generación de tablas, sería comprobar las dependencias
     * de tablas para saber cuántas tablas usan una constraint de cada tabla para poder
     * realizar cambios en la base de datos y tener una visión más nítida de la misma en
     * cualquier momento, si bien, esa estructura no será clara hasta que no se hayan leído
     * todas, y si hay un cambio entre medias, pues igual la única solución viable es
     * determinarlo por la propia base de datos.
     *
     * @author  Rafael San José Tovar <info@rsanjoseo.com>
     * @version 2023.0105
     *
     * @param string $tableName
     * @param bool   $create
     *
     * @return bool
     */
    private static function checkStructure(string $tableName, string $path, bool $create = true): bool
    {
        // Si el dato ya ha sido cargado, retornamos porque no hay nada que hacer.
        if (!empty(self::$bbddStructure[$tableName])) {
            return true;
        }

        // Si no está, pero está cacheado, se recupera de la caché y se retorna.
        self::$bbddStructure[$tableName] = YamlSchema::loadCacheYamlFile(YamlSchema::YAML_CACHE_TABLES_DIR, $tableName);
        if (!empty(self::$bbddStructure[$tableName])) {
            return true;
        }

        // Si no está cacheado, entonces hay que comprobar si hay cambios en la estructura y regenerarla.
        self::$bbddStructure[$tableName] = self::checkTable($tableName, $path, $create);

        dump(self::$bbddStructure);

        if (DB::tableExists($tableName)) {
            dump('La tabla ' . $tableName . ' existe');
            if (!self::updateTable($tableName)) {
                dump(Translator::trans('table_creation_error', ['%tablename%' => $tableName]));
                FlashMessages::setError(Translator::trans('table_creation_error', ['%tablename%' => $tableName]));
            }
        } else {
            dump('La tabla ' . $tableName . ' NO existe');
            if (!self::createTable($tableName)) {
                dump(Translator::trans('table_creation_error', ['%tablename%' => $tableName]));
                FlashMessages::setError(Translator::trans('table_creation_error', ['%tablename%' => $tableName]));
            }
        }

        die('Por aquí vamos ahora...');

        if (!YamlSchema::saveCacheYamlFile(YamlSchema::YAML_CACHE_TABLES_DIR, $tableName, self::$bbddStructure[$tableName])) {
            Debug::message('No se ha podido guardar la información de caché para la tabla ' . $tableName);
            return false;
        }
        return true;
    }

    /**
     * Obtiene el tipo genérico del tipo de dato que se le ha pasado.
     *
     * @author  Rafael San José Tovar <info@rsanjoseo.com>
     * @version 2023.0101
     *
     * @param string $type
     *
     * @return string
     */
    public static function getTypeOf(string $type): string
    {
        foreach (DB::getDataTypes() as $index => $types) {
            if (in_array(strtolower($type), $types)) {
                return $index;
            }
        }
        Debug::message($type . ' not found in DBSchema::getTypeOf()');
        return 'text';
    }

    private static function splitType(string $originalType): array
    {
        $replacesSources = [
            'character varying',
            // 'timestamp without time zone',
            'double precision',
        ];
        $replacesDestination = [
            'varchar',
            // 'timestamp',
            'double',
        ];
        $modifiedType = (str_replace($replacesSources, $replacesDestination, $originalType));

        if ($originalType !== $modifiedType) {
            Debug::message("XML: Uso de '{$originalType}' en lugar de '{$modifiedType}'.");
        }
        $explode = explode(' ', strtolower($modifiedType));

        $pos = strpos($explode[0], '(');
        if ($pos > 0) {
            $begin = $pos + 1;
            $end = strpos($explode[0], ')');
            $type = substr($explode[0], 0, $pos);
            $length = substr($explode[0], $begin, $end - $begin);
        } else {
            $type = $explode[0];
            $length = null;
        }

        $pos = array_search('unsigned', $explode, true);
        $unsigned = $pos ? 'yes' : 'no';

        $pos = array_search('zerofill', $explode, true);
        $zerofill = $pos ? 'yes' : 'no';

        return ['type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'zerofill' => $zerofill];
    }

    /**
     * Toma los datos del fichero de definición de una tabla y genera el definitivo.
     *
     * TODO: Tiene que recopilar la estructura tal y como la tiene que retornar la base de datos.
     *       Se le pasan los datos de definición a SqlHelper para que retorne la estructura física final.
     *       Se generan datos comunes a cualquier base de datos para poder trabajar con comodidad.
     *
     * Si tomamos como ejemplo MySQL, se invocará a SqlMySql para que retorne algo parecido a ésto:
     *
     *   "database" => array:7 [▼
     *     "field" => "id"
     *     "type" => "bigint"
     *     "length" => 20
     *     "default" => null
     *     "nullable" => "YES"
     *     "primary" => ""
     *     "autoincrement" => 1
     *   ]
     *
     * En SqlMySql existe un normalize que convierte la respuesta a ese aspecto.
     *
     *   "logical" => array:9 [▼
     *     "key" => "id"
     *     "type" => "autoincrement"
     *     "dbtype" => "bigint (20) unsigned"
     *     "realtype" => "bigint"
     *     "generictype" => "integer"
     *     "null" => "YES"
     *     "default" => null
     *     "min" => 0
     *     "max" => 1.844674407371E+19
     *   ]
     *
     * Porque con esa estructura será mucho más facil crear el campo o compararlo con el de la base de datos.
     *
     * Aparte, se necesitará información adicional, que será común a todas las bases de datos:
     *
     * @author  Rafael San José Tovar <info@rsanjoseo.com>
     * @version 2022.1224
     *
     * @param array $structure
     *
     * @return array
     */
    protected static function normalize(array $structure): array
    {
        $column = [];
        $key = (string) $structure['key'];
        $type = (string) $structure['type'];
        $column['key'] = $key;

        /**
         * Entrada:
         * - type es el tipo lógico del campo y tiene que estar definido como índice en
         *   TYPES, o ser uno de los predefinidos como 'autoincrement', 'relationship', etc.
         *
         * Salida:
         * - type queda intacto.
         * - dbtype es como queda definido en la tabla, por ejemplo, varchar(20)
         * - realtype es el tipo resultado, por ejemplo varchar (sin el tamaño)
         * - generictype es uno de los índices de TYPE. P.E. autoincrement se cambiará por integer
         *
         */

        $column['type'] = $type;
        switch ($type) {
            case 'autoincrement':
            case 'relationship':
                $colType = self::DB_INDEX_TYPE;
                break;
            case 'boolean':
                $colType = 'tinyint(1) unsigned';
                break;
            default:
                $colType = $type;
        }

        $typeArray = static::splitType($colType);
        /**
         * ^ array:4 [▼
         *        "type" => "bigint"
         *        "length" => null
         *        "unsigned" => "yes"
         *        "zerofill" => "no"
         * ]
         */
        $type = $typeArray['type'];
        $length = $typeArray['length'] ?? $structure['length'];
        $unsigned = $typeArray['unsigned'] === 'yes';
        $zerofill = $typeArray['zerofill'] === 'yes';
        $genericType = static::getTypeOf($type);

        $column['dbtype'] = $colType;
        $column['realtype'] = $type;
        $column['generictype'] = $genericType;

        $column['null'] = 'YES';
        if ($structure['null'] && mb_strtolower($structure['null']) == 'no') {
            $column['null'] = 'NO';
        }

        if (empty($structure['default'])) {
            $column['default'] = null;
        } else {
            $column['default'] = (string) $structure['default'];
        }

        /**
         * Pueden existir otras definiciones de limitaciones físicas como min y max
         * De existir, tienen que ser contempladas en el método test y tener mayor peso que
         * la limitación en plantilla.
         */
        foreach (['min', 'max'] as $field) {
            if (isset($structure[$field])) {
                $column[$field] = (string) $structure[$field];
            }
        }

        if (isset($structure['comment'])) {
            $column['comentario'] = (string) $structure['comment'];
        }

        if (isset($structure['default'])) {
            $column['default'] = trim($structure['default'], " \"'`");
        }

        switch ($genericType) {
            case 'text':
                $column['dbtype'] = 'varchar(' . $length . ')';
                $column['maxlength'] = $length;
                break;
            case 'integer':
                /**
                 * Lo primero es ver la capacidad física máxima según el tipo de dato.
                 */
                $bytes = 4;
                switch ($type) {
                    case 'tinyint':
                        $bytes = 1;
                        break;
                    case 'smallint':
                        $bytes = 2;
                        break;
                    case 'mediumint':
                        $bytes = 3;
                        break;
                    case 'int':
                        $bytes = 4;
                        break;
                    case 'bigint':
                        $bytes = 8;
                        break;
                }
                $bits = 8 * (int) $bytes;
                $physicalMaxLength = 2 ** $bits;

                /**
                 * $minDataLength y $maxDataLength contendrán el mínimo y máximo valor que puede contener el campo.
                 */
                $minDataLength = $unsigned ? 0 : -$physicalMaxLength / 2;
                $maxDataLength = ($unsigned ? $physicalMaxLength : $physicalMaxLength / 2) - 1;

                /**
                 * De momento, se asignan los límites máximos por el tipo de dato.
                 * En $min y $max, iremos arrastrando los límites conforme se vayan comprobando.
                 * $min nunca podrá ser menor que $minDataLength.
                 * $max nunca podrá ser mayor que $maxDataLength.
                 */
                $min = $minDataLength;
                $max = $maxDataLength;

                /**
                 * Se puede hacer una limitación física Se puede haber definido en el xml un min y un max.
                 * A todos los efectos, lo definido en el XML como min o max se toma como limitación
                 * física del campo.
                 */
                if (isset($structure['min'])) {
                    $minXmlLength = $structure['min'];
                    if ($minXmlLength > $minDataLength) {
                        $min = $minXmlLength;
                    } else {
                        Debug::message("({$key}): Se ha especificado un min {$minXmlLength} en el XML, pero por el tipo de datos, el mínimo es {$minDataLength}.");
                    }
                }
                if (isset($structure['max'])) {
                    $maxXmlLength = $structure['max'];
                    if ($maxXmlLength < $maxDataLength) {
                        $max = $maxXmlLength;
                    } else {
                        Debug::message("({$key}): Se ha especificado un min {$maxXmlLength} en el XML, pero por el tipo de datos, el máximo es {$maxDataLength}.");
                    }
                }

                $column['min'] = $min;
                $column['max'] = $max;
                break;
            default:
                // ???
        }

        return $column;
    }

    public function compare_columns($table_name, $xml_cols, $db_cols)
    {
        $sql = '';

        foreach ($xml_cols as $xml_col) {
            if (mb_strtolower($xml_col['tipo']) == 'integer') {
                /**
                 * Desde la pestaña avanzado el panel de control se puede cambiar
                 * el tipo de entero a usar en las columnas.
                 */
                $xml_col['tipo'] = FS_DB_INTEGER;
            }

            /**
             * Si el campo no está en la tabla, procedemos a su creación
             */
            $db_col = $this->search_in_array($db_cols, 'name', $xml_col['nombre']);
            if (empty($db_col)) {
                $sql .= 'ALTER TABLE `' . $table_name . '` ADD `' . $xml_col['nombre'] . '` ';
                if ($xml_col['tipo'] == 'serial') {
                    $sql .= '`' . $xml_col['nombre'] . '` ' . constant('FS_DB_INTEGER') . ' NOT NULL AUTO_INCREMENT;';
                    continue;
                }
                if ($xml_col['tipo'] == 'autoincrement') {
                    $sql .= '`' . $xml_col['nombre'] . '` ' . constant('DB_INDEX_TYPE') . ' NOT NULL AUTO_INCREMENT;';
                    continue;
                }
                if ($xml_col['tipo'] == 'relationship') {
                    $xml_col['tipo'] = constant('DB_INDEX_TYPE');
                }

                $sql .= $xml_col['tipo'];
                $sql .= ($xml_col['nulo'] == 'NO') ? " NOT NULL" : " NULL";

                if ($xml_col['defecto'] !== null) {
                    $sql .= " DEFAULT " . $xml_col['defecto'];
                } elseif ($xml_col['nulo'] == 'YES') {
                    $sql .= " DEFAULT NULL";
                }

                $sql .= ';';

                continue;
            }

            /**
             * Si el campo es un autoincremental o relacionado a uno, asignamos el tipo correcto para la constraint.
             * Si además es el índice, nos aseguramos de que no pueda ser nulo.
             */
            if (in_array($xml_col['tipo'], ['autoincrement', 'relationship'])) {
                if ($xml_col['tipo'] === 'autoincrement') {
                    $xml_col['nulo'] = 'NO';
                }
                $xml_col['tipo'] = constant('DB_INDEX_TYPE');
            }

            /// columna ya presente en db_cols. La modificamos
            if (!$this->compare_data_types($db_col['type'], $xml_col['tipo'])) {
                // Buscar todas las constraints relacionadas con este campo y eliminarlas
                foreach ($this->get_referenced_field_constraint($table_name, $xml_col['nombre']) as $pos => $constraint) {
                    $sql .= "ALTER TABLE `" . $constraint['TABLE_NAME'] . "` DROP FOREIGN KEY " . $constraint['CONSTRAINT_NAME'] . ";";
                }
                $sql .= 'ALTER TABLE `' . $table_name . '` MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ';';
            }

            if ($db_col['is_nullable'] == $xml_col['nulo']) {
                /// do nothing
            } elseif ($xml_col['nulo'] == 'YES') {
                $sql .= 'ALTER TABLE `' . $table_name . '` MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NULL;';
            } else {
                $sql .= 'ALTER TABLE `' . $table_name . '` MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'] . ' NOT NULL;';
            }

            if ($this->compare_defaults($db_col['default'], $xml_col['defecto'])) {
                /// do nothing
            } elseif (is_null($xml_col['defecto'])) {
                if ($this->exists_index($table_name, $db_col['name']) && !$this->unique_equals($table_name, $db_col, $xml_col)) {
                    $sql .= 'ALTER TABLE `' . $table_name . '` ALTER `' . $xml_col['nombre'] . '` DROP DEFAULT;';
                }
            } elseif (mb_strtolower(substr($xml_col['defecto'], 0, 9)) == "nextval('") { /// nextval es para postgresql
                if ($db_col['extra'] != 'auto_increment') {
                    $sql .= 'ALTER TABLE `' . $table_name . '` MODIFY `' . $xml_col['nombre'] . '` ' . $xml_col['tipo'];
                    $sql .= ($xml_col['nulo'] == 'YES') ? ' NULL AUTO_INCREMENT;' : ' NOT NULL AUTO_INCREMENT;';
                }
            } else {
                if ($db_col['default'] != $xml_col['defecto'] && ($db_col['default'] != null && $xml_col['defecto'] == 'NULL')) {
                    $sql .= 'ALTER TABLE `' . $table_name . '` ALTER `' . $xml_col['nombre'] . '` SET DEFAULT ' . $xml_col['defecto'] . ";";
                }
            }
        }

        return $this->fix_postgresql($sql);
    }

    /**
     * Create a table in the database.
     * Build the default fields, indexes and values defined in the model.
     *
     * @param string $tableName
     *
     * @return bool
     * @throws DebugBarException
     */

    private static function createTable(string $tableName): bool
    {
        $tabla = self::$bbddStructure[$tableName];
        $sql = self::createFields($tableName, $tabla['fields']);

        foreach ($tabla['keys'] as $name => $index) {
            $sql .= self::createIndex($tableName, $name, $index);
        }
        // TODO: values no existe, hay que cargar los datos de seeds.
        if (isset($tabla['values'])) {
            $sql .= self::setValues($tableName, $tabla['values']);
        }

        return Engine::exec($sql);
    }

    private static function updateField(string $tableName, string $fieldName, array $structure): string
    {
        dump([
            'tablename' => $tableName,
            'fieldname' => $fieldName,
            'new structure' => self::$bbddStructure[$tableName]['fields'][$fieldName],
            'structure' => $structure,
        ]);
        return '';
    }

    private static function updateTable(string $tableName): bool
    {
        $yamlStructure = self::$bbddStructure[$tableName];
        $dbStructure = DB::getColumns($tableName);

        foreach ($yamlStructure['fields'] as $field => $newStructure) {
            $oldDb = $dbStructure[$field];
            $newDb = $newStructure['db'];

            $dif = array_diff($oldDb, $newDb);
            $data = [
                'field' => $field,
                'dbStructure' => $dbStructure[$field],
                'fields of ' . $tableName => $newStructure['db'],
                'oldDb' => $oldDb,
                'newDb' => $newDb,
            ];
            if (count($dif) > 0) {
                $data['diferencias 1'] = $dif;
                $data['diferencias 2'] = array_diff($newDb, $oldDb);
                $data['sql'] = DB::modify($tableName, $oldDb, $newDb);
            }

            dump($data);
        }

        die('Here');

        return Engine::exec($sql);
    }

    /**
     * Build the SQL statement to create the fields in the table.
     * It can also create the primary key if the auto_increment attribute is defined.
     *
     * @param string $tablename
     * @param array  $fieldList
     *
     * @return string
     */
    protected static function createFields(string $tablename, array $fieldList): string
    {
        $tablenameWithPrefix = Config::$dbPrefix . $tablename;

        $sql = "CREATE TABLE $tablenameWithPrefix ( ";
        foreach ($fieldList as $index => $col) {
            if (!isset($col['dbtype'])) {
                die('Tipo no especificado en createTable ' . $index);
            }

            $sql .= '`' . $index . '` ' . $col['dbtype'];
            $nulo = isset($col['null']) && $col['null'];

            $sql .= ($nulo ? '' : ' NOT') . ' NULL';

            if (isset($col['extra']) && (strtolower($col['extra']) == 'auto_increment')) {
                $sql .= ' PRIMARY KEY AUTO_INCREMENT';
            }

            $tmpDefecto = $col['default'] ?? null;
            $defecto = '';
            if (isset($tmpDefecto)) {
                if ($tmpDefecto == 'CURRENT_TIMESTAMP') {
                    $defecto = "$tmpDefecto";
                } else {
                    $defecto = "'$tmpDefecto'";
                }
            } else {
                if ($nulo) {
                    $defecto = 'NULL';
                }
            }

            if ($defecto != '') {
                $sql .= ' DEFAULT ' . $defecto;
            }

            $sql .= ', ';
        }
        $sql = substr($sql, 0, -2); // Quitamos la coma y el espacio del final
        $sql .= ') ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;' . self::CRLF;

        return $sql;
    }

    /**
     * Create the SQL statements for the construction of one index.
     * In the case of the primary index, it is not necessary if it is auto_increment.
     *
     * TODO:
     *
     * Moreover, it should not be defined if it is auto_increment because it would
     * generate an error when it already exists.
     *
     * @param string $tableName
     * @param string $indexname
     * @param array  $indexData
     *
     * @return string
     */
    protected static function createIndex($tableName, $indexname, $indexData)
    {
        $sql = "ALTER TABLE $tableName ADD CONSTRAINT $indexname ";

        $command = '';
        // https://www.w3schools.com/sql/sql_primarykey.asp
        // ALTER TABLE Persons ADD CONSTRAINT PK_Person PRIMARY KEY (ID,LastName);
        if (isset($indexData['PRIMARY'])) {
            $command = 'PRIMARY KEY ';
            $fields = $indexData['PRIMARY'];
        }

        // https://www.w3schools.com/sql/sql_create_index.asp
        // CREATE INDEX idx_pname ON Persons (LastName, FirstName);
        if (isset($indexData['INDEX'])) {
            $command = 'INDEX ';
            $fields = $indexData['INDEX'];
        }

        // https://www.w3schools.com/sql/sql_unique.asp
        // ALTER TABLE Persons ADD CONSTRAINT UC_Person UNIQUE (ID,LastName);
        if (isset($indexData['UNIQUE'])) {
            $command = 'UNIQUE INDEX ';
            $fields = $indexData['UNIQUE'];
        }

        if ($command == '') {
            // https://www.w3schools.com/sql/sql_foreignkey.asp
            // ALTER TABLE Orders ADD CONSTRAINT FK_PersonOrder FOREIGN KEY (PersonID) REFERENCES Persons(PersonID);
            if (isset($indexData['FOREIGN'])) {
                $command = 'FOREIGN KEY ';
                $foreignField = $indexData['FOREIGN'];
                if (isset($indexData['REFERENCES'])) {
                    $references = $indexData['REFERENCES'];
                    if (!is_array($references)) {
                        die('Esperaba un array en REFERENCES: ' . $tableName . '/' . $indexname);
                    }
                    if (count($references) != 1) {
                        die('Esperaba un array de 1 elemento en REFERENCES: ' . $tableName . '/' . $indexname);
                    }
                    $refTable = key($references);
                    $fields = '(' . implode(',', $references) . ')';
                } else {
                    die('FOREIGN necesita REFERENCES en ' . $tableName . '/' . $indexname);
                }

                $sql .= $command . ' ' . $foreignField . ' REFERENCES ' . $refTable . $fields;

                if (isset($indexData['ON']) && is_array($indexData['ON'])) {
                    foreach ($indexData['ON'] as $key => $value) {
                        $sql .= ' ON ' . $key . ' ' . $value . ', ';
                    }
                    $sql = substr($sql, 0, -2); // Quitamos el ', ' de detrás
                }
            }
        } else {
            if (is_array($fields)) {
                $fields = '(' . implode(',', $fields) . ')';
            } else {
                $fields = "($fields)";
            }

            if ($command == 'INDEX ') {
                $sql = "CREATE INDEX {$indexname} ON {$tableName}" . $fields;
            } else {
                $sql .= $command . ' ' . $fields;
            }
        }

        return $sql . ';' . self::CRLF;
    }

    /**
     * Create the SQL statements to fill the table with default data.
     *
     * @param string $tableName
     * @param array  $values
     *
     * @return string
     */
    protected static function setValues(string $tableName, array $values): string
    {
        $sql = "INSERT INTO $tableName ";
        $header = true;
        foreach ($values as $value) {
            $fields = "(";
            $datos = "(";
            foreach ($value as $fname => $fvalue) {
                $fields .= $fname . ", ";
                $datos .= "'$fvalue'" . ", ";
            }
            $fields = substr($fields, 0, -2) . ") ";
            $datos = substr($datos, 0, -2) . "), ";

            if ($header) {
                $sql .= $fields . " VALUES ";
                $header = false;
            }

            $sql .= $datos;
        }

        return substr($sql, 0, -2) . self::CRLF;
    }
}
