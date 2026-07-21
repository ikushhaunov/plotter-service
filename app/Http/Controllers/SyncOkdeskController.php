    /**
     * Тестовый синхронный запуск через Artisan::call()
     */
    public function syncTest(Request $request)
    {
        $limit = $request->get('limit', 20);
        $startId = $request->get('start-id', 0);
        
        try {
            // Запускаем artisan команду напрямую через Laravel
            $exitCode = \Artisan::call('sync:okdesk', [
                '--limit' => (int)$limit,
                '--start-id' => (int)$startId,
            ]);
            
            // Получаем вывод команды
            $output = \Artisan::output();
            
            return response()->json([
                'status' => 'success',
                'exit_code' => $exitCode,
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }