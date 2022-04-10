<?php

class Cron {

    private static array $instances = [];

    private int $enabled = 0;

    /**
     * @var array
     *  ['key', 'handler', 'recurrence']
     */
    private array $actions = [];
    private array $schedules = [];

    /**
     * @throws Exception
     */
    protected function __construct($config) {
        if (empty($config) || !is_array($config)) {
            throw new Exception('Wrong config data.');
        }

        foreach ($config as $property_name => $value) {
            $this->{$property_name} = $value;
        }

        if ($this->enabled) {
            $this->initHooks();
            $this->registerSchedule();
        }
    }

    /**
     * @throws Exception
     */
    public static function getInstance($config) {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static($config);
        }

        return self::$instances[$cls];
    }

    public function registerSchedule() {
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                if (!wp_next_scheduled($action['key'])) {
                    wp_schedule_event(time(), $action['recurrence'], $action['key']);
                }
            }
        }
    }

    public function clearSchedule($action_key) {
        wp_clear_scheduled_hook($action_key);
    }

    public function clearAllSchedules() {
        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                wp_clear_scheduled_hook($action['key']);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function handleCronSchedule($schedules) {
        if (empty($this->schedules)) {
            return $schedules;
        }

        foreach ($this->schedules as $schedule_key => $interval) {
            if (isset($schedules[$schedule_key])) {
                throw new Exception("Someone already use the schedule_key - $schedule_key");
            }
            $schedules[$schedule_key] = [
                'interval' => $interval['interval'],
                'display' => $interval['display'],
            ];
        }

        return $schedules;
    }

    private function initHooks() {
        add_filter('cron_schedules', [$this, 'handleCronSchedule']);

        if (!empty($this->actions)) {
            foreach ($this->actions as $action) {
                add_action($action['key'], $action['handler']);
            }
        }
    }

    /**
     * @return array
     */
    public function getActions(): array {
        return $this->actions;
    }

    /**
     * @param array $actions
     */
    public function setActions(array $actions): void {
        $this->actions = $actions;
    }

    /**
     * @return array
     */
    public function getSchedules(): array {
        return $this->schedules;
    }

    /**
     * @param array $schedules
     */
    public function setSchedules(array $schedules): void {
        $this->schedules = $schedules;
    }

    /**
     * @return int
     */
    public function isEnabled(): int {
        return $this->enabled;
    }

    public function disable() {
        $this->enabled = true;
    }

    public function enable() {
        $this->enabled = false;
    }


}
