<?php

namespace hrustbb2\arrayproc;

class ArrayProcessor {

    /**
     * Объект содержащий данные неоюходимые для маппинга
     * строится по конфигу передаваемому в метод process
     *
     * @var \stdClass Карта данных
     */
    private $dataMap;

    /**
     * @var \stdClass Результат
     */
    private $result;

    /**
     * Карта путей до вложенных данных
     *
     * @param array $conf
     * @param array $currentPath
     */
    private function buildPathMap($conf, $currentPath = [])
    {
        foreach ($conf as $key=>$field){
            if(is_array($field)){
                $prefix = $field['prefix'];
                $currentPath[$prefix] = $key;
                $this->dataMap->{$prefix} = new \stdClass();
                $this->dataMap->{$prefix}->path = $currentPath;
                $this->dataMap->{$prefix}->data = new \stdClass();
                $this->dataMap->{$prefix}->fields = [];
                $this->buildPathMap($field, $currentPath);
                $currentPath = [];
            }
        }
    }

    /**
     * Карта имен полей
     *
     * @param \stdClass $line
     */
    private function buildFieldsMap($line)
    {
        foreach ($this->dataMap as $prefix=>$path){
            foreach ($line as $field=>$val){
                $fieldSegments = explode('_', $field);
                $fieldPrefix = array_shift($fieldSegments);
                $fieldName = join('_', $fieldSegments);
                if($prefix == $fieldPrefix . '_'){
                    $this->dataMap->{$fieldPrefix . '_'}->fields[] = $fieldName;
                }
            }
        }
    }

    /**
     * Карта данных
     * В итоге, если, например, $conf имеет вид:
     * [
     *      'prefix' => 'book_',
     *      'authors' => [
     *          'prefix' => 'author_'
     *      ]
     * ]
     * А $firstRow:
     * [
     *      'book_id' => 1,
     *      'book_name' => 'blah blah blah',
     *      'author_id' => 1,
     *      'author_name' => 'Dostoyevsky'
     * ]
     * То $this->dataMap будет иметь следующий вид:
     * {
     *      book_: {
     *          path: []
     *          data: {}
     *          fields: ['id', 'name']
     *      },
     *      author_: {
     *          path: ['authors']
     *          data: {}
     *          fields: ['id', 'name']
     *      {
     * }
     *
     * @param array $conf
     * @param \stdClass $firstRow
     */
    private function buildDataMap($conf, $firstRow)
    {
        // Начальная инициализация dataMap
        $this->dataMap = new \stdClass();
        $this->dataMap->{$conf['prefix']} = new \stdClass();
        // path хранит массив строк представляющих собой путь к данным во вложенной структуре
        // итогового массива
        $this->dataMap->{$conf['prefix']}->path = [];
        // уже смапенные данные, чтобы не считывать одно и то же из входного массива несколько раз
        $this->dataMap->{$conf['prefix']}->data = new \stdClass();
        // имена полей для данного префикса
        $this->dataMap->{$conf['prefix']}->fields = [];
        $this->buildPathMap($conf);
        $this->buildFieldsMap($firstRow);
    }

    /**
     * @param array $conf
     * @param \stdClass[] $array
     * @return $this
     */
    public function process($conf, $array)
    {
        $this->result = new \stdClass();
        if(empty($array)){
            return $this;
        }
        $this->buildDataMap($conf, $array[0]);

        //Читаем каждую строку входящего массива
        foreach ($array as $line){
            //Первичный ключ
            $rowId = $line->{$conf['prefix'] . 'id'};
            //Если в результирующем объекте нет записи, создаем ее
            if(!isset($this->result->{$rowId})){
                $this->result->{$rowId} = new \stdClass();
            }
            $currentRow = $this->result->{$rowId};

            //Читаем данные по префиксам полей
            foreach ($this->dataMap as $prefix=>$path){
                //Первичный ключ (например author_id, book_id)
                $id = $line->{$prefix . 'id'};
                if($id === null){
                    continue;
                }

                //Сохраняем данные, чтобы не считывать несколько раз одно и то же
                if(!isset($path->data->{$id})){
                    $path->data->{$id} = new \stdClass();
                    $fields = $path->fields;
                    foreach ($fields as $field){
                        $val = $line->{$prefix . $field};
                        $path->data->{$id}->{$field} = $val;
                        // $path->path - пустой массив
                        if(!$path->path){
                            $currentRow->{$field} = $val;
                        }
                    }
                }

                // "пробираемя" по пути вложености данных
                $c = $currentRow;
                foreach ($path->path as $p => $step) {
                    $_id = $line->{$p . 'id'};
                    if (!isset($c->{$step})) {
                        $c->{$step} = new \stdClass();
                    }
                    if (!isset($c->{$step}->{$_id})) {
                        $c->{$step}->{$_id} = new \stdClass();
                    }
                    if (next($path->path)) {
                        $c = $c->{$step}->{$_id};
                    }else{
                        $c = $c->{$step};
                    }
                }
                //
                if($path->path){
                    $c->{$id} = clone $path->data->{$id};
                }
            }
        }

        return $this;
    }

    /**
     * Результат как объект
     *
     * @return \stdClass
     */
    public function resultObj()
    {
        return $this->result;
    }

    /**
     * Результат как массив
     *
     * @return array
     */
    public function resultArray()
    {
        $str = json_encode($this->result);
        return json_decode($str, true);
    }

}