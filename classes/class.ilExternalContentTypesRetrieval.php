<?php

class ilExternalContentTypesRetrieval implements \ILIAS\UI\Component\Table\DataRetrieval
{
    protected \ILIAS\UI\Factory $ui_factory;
    protected \ILIAS\UI\Renderer $ui_renderer;
    protected ilExternalContentPlugin $plugin;
    protected ilLanguage $lng;
    
    public function __construct(
        \ILIAS\UI\Factory $ui_factory, 
        \ILIAS\UI\Renderer $ui_renderer,
        ilLanguage $lng,
        ilExternalContentPlugin $plugin,
    ) {
        $this->ui_factory = $ui_factory;
        $this->ui_renderer = $ui_renderer;
        $this->lng = $lng;
        $this->plugin = $plugin;
    }
    
    
    public function getTable(): \ILIAS\UI\Implementation\Component\Table\Data
    {
        return $this->ui_factory->table()->data(
            $this->plugin->txt('content_types'),
            $this->getColumns(),
            $this
        )->withAdditionalParameters(['extended' => true]);
    }
    
    public function getColumns() : array
    {
        $f = $this->ui_factory;
        return [
            'type_id' => $f->table()->column()->number($this->lng->txt('id'))
                           ->withIsSortable(false),
            'type_name' => $f->table()->column()->text($this->lng->txt('name'))
                             ->withIsSortable(true),
            'title' => $f->table()->column()->text($this->lng->txt('title'))
                         ->withIsSortable(true),
            'availability' => $f->table()->column()->text($this->plugin->txt('type_availability'))
                                ->withIsSortable(true),
            'usages' => $f->table()->column()->number($this->plugin->txt('untrashed_usages'))
                          ->withIsSortable(true)
        ];
    }
    
    
    public function getRows(
        \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
        array $visible_column_ids,
        \ILIAS\Data\Range $range,
        \ILIAS\Data\Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ) : Generator {
        
        $rows = $this->readData($filter_data, $additional_parameters);

        if ($order) {
            list($order_field, $order_direction) = $order->join([], fn ($ret, $key, $value) => [$key, $value]);
            usort($rows, fn ($a, $b) => $a[$order_field] <=> $b[$order_field]);
            if ($order_direction === 'DESC') {
                $rows = array_reverse($rows);
            }
        }
        if ($range) {
            $rows = array_slice($rows, max($range->getStart() - 1, 0), $range->getLength());
        }

        foreach ($rows as $row) {
            
            $row['type_id'] = (int) $row['type_id'];
            if (isset($row['usages'])) {
                $row['usages'] = (int) $row['usages'];
            }
            
            yield $row_builder->buildDataRow((string) $row['type_id'], $row);
        }
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters) : ?int
    {
        // this hides the paging ui controls
        // return null;
        
        // this shows the paging ui controls
        return count($this->readData($filter_data, $additional_parameters));
    }
    
    protected function readData(?array $filter_data, ?array $additional_parameters) : array
    {
        $extended = false;
        if (isset($additional_parameters) && isset($additional_parameters['extended'])) {
            $extended = (bool) $additional_parameters['extended'];
        }
        
        $availability = null;
        if (isset($filter_data) && isset($filter_data['availability'])) {
            $availability = (int) $filter_data['availability'];
        }

        return ilExternalContentType::_getTypesData(true, $availability);
    }
}