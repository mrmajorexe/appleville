<?php
require_once('config.php');

echo "Created by @MrMajorExe on telegram\n";
system("xdg-open https://youtube.com/@ketuakochenk");
while (true) {
    foreach($cookies as $account_name => $current_cookie) {
        echo "\n==================================================\n";
        echo "[" . date("H:i:s") . "] 👨‍🌾 Memproses Akun: $account_name\n";
        
        $state = getGameState($current_cookie);
        $ap_exchange_status = getApExchangeStatus($current_cookie);

        if ($state === null) {
            echo "[" . date("H:i:s") . "] ❌ Gagal mendapatkan status game. Lanjut...\n";
            sleep($jeda_antar_akun);
            continue;
        }

        $my_coins = $state['coins'];
        $my_ap = $state['ap']; 
        $my_plots = $state['plots']; 
        $num_plots = count($my_plots); 
        $next_plot_price_info = $state['nextPlotPrice']; 
        $my_level = 1; 
        foreach ($level_map as $exp => $lvl) { 
            if ($state['xp'] >= $exp) { 
                $my_level = $lvl; 
            } else { 
                break; 
            } 
        }
        
        echo "[" . date("H:i:s") . "] 🌟 Lv: $my_level | 💰 Coins: " . number_format($my_coins, 2) . " | 💎 AP: $my_ap | 🌱 Lahan: $num_plots/11\n";
        
        //panen
        $plots_to_harvest = [];
        foreach ($my_plots as $plot) {
            if ($plot['seed'] !== null && strtotime($plot['seed']['endsAt']) <= time()) $plots_to_harvest[] = $plot['slotIndex'];
        }
        if (!empty($plots_to_harvest)) {
            echo "   -> 🌾 Aksi Panen: Ditemukan " . count($plots_to_harvest) . " plot siap panen.\n";
            $harvest_result = harvest($current_cookie, $plots_to_harvest);
            if ($harvest_result) {
                $coinGain = $harvest_result['totalCoinsEarned'] ?? 0;
                $apGain = $harvest_result['totalApEarned'] ?? 0;
                $xpGain = $harvest_result['totalXpGained'] ?? 0;
                $profit_text = ($coinGain > 0) ? "+{$coinGain} Coins" : "+{$apGain} AP";
                echo "      ✅ Sukses! Keuntungan: {$profit_text}, +{$xpGain} XP.\n";
                echo "   -> ✨ Memuat ulang status...\n";
                $state = getGameState($current_cookie); 
                $ap_exchange_status = getApExchangeStatus($current_cookie);
                if($state === null) continue;
                $my_coins = $state['coins']; 
                $my_ap = $state['ap']; 
                $my_plots = $state['plots'];
                echo "[" . date("H:i:s") . "] 🌟 Status Baru: 💰 Coins: " . number_format($my_coins, 2) . " | 💎 AP: $my_ap\n";
            }
        }
        
        //logika
        $stop_and_continue = false;

        //algo beli plot
        if ($num_plots < 11) {
            echo "   -> 🎯 Prioritas Utama: Mencapai 11 plot.\n";
            if ($next_plot_price_info['currency'] === 'coins') {
                if ($my_coins >= $next_plot_price_info['amount'] + $cadangan_coins_tetap) {
                    echo "   -> 🏠 Aksi Investasi (Coins): Dana cukup! Membeli plot baru...\n";
                    if (buyPlot($current_cookie)) { 
                        echo "      ✅ Sukses! Refresh di siklus berikutnya.\n"; 
                        $stop_and_continue = true; 
                    }
                }
            } elseif ($next_plot_price_info['currency'] === 'AP') {
                if ($my_ap >= $next_plot_price_info['amount']) {
                    echo "   -> 🏠 Aksi Investasi (AP): Dana cukup! Membeli plot baru...\n";
                    if (buyPlot($current_cookie)) { 
                        echo "      ✅ Sukses! Refresh di siklus berikutnya.\n"; 
                        $stop_and_continue = true; 
                    }
                }
            }
        }
        if($stop_and_continue) { 
            sleep($jeda_antar_akun); 
            continue; 
        }

        //strategi golden apel / weat
        $target_plant = 'wheat';
        $reason = 'Farming Coins untuk AP Exchange atau beli plot.';
        $ap_exchange_available = false;
        foreach ($ap_exchange_status as $status) {
            if ($status['remaining'] > 0 && $my_level >= $status['minLevel']) { 
                $ap_exchange_available = true; break; 
            }
        }
        if ($num_plots >= 11 && !$ap_exchange_available) {
            $target_plant = 'golden-apple';
            $reason = 'Semua plot terbeli & AP Exchange limit. Farming AP langsung.';
        }
        echo "   -> 💡 Strategi Tanam: Menanam '" . ucfirst($target_plant) . "'. Alasan: $reason\n";

        // strategi tukar koin ke ap
        if ($target_plant === 'wheat' && $ap_exchange_available) {
            $best_exchange = null; $best_rate = INF;
            foreach ($ap_exchange_status as $status) {
                if ($status['remaining'] > 0 && $my_level >= $status['minLevel']) {
                    $rate = $status['price']['amount'] / $status['yield']['amount'];
                    if ($rate < $best_rate) {
                        $best_rate = $rate; 
                        $best_exchange = $status; 
                    }
                }
            }
            if ($best_exchange && $my_coins > ($best_exchange['price']['amount'] + $cadangan_coins_tetap)) {
                echo "   -> 🏦 Aksi Tukar AP: Dana lebih terdeteksi! Mencoba '{$best_exchange['name']}'...\n";
                $exchange_result = exchangeForAp($current_cookie, $best_exchange['key']);
                if ($exchange_result && $exchange_result['success']) {
                    echo "      ✅ Sukses! Keuntungan: +{$exchange_result['apGained']} AP\n";
                    $my_coins -= $exchange_result['totalCost'];
                }
            }
        }

        // strategi tanam bibit + booster
        $empty_plots = [];
        foreach($my_plots as $plot) { 
            if ($plot['seed'] === null) $empty_plots[] = $plot; 
        }
        
        if (!empty($empty_plots)) {
            $num_empty_plots = count($empty_plots);
            $plant_info = $plant_data[$target_plant];
            $cost_per_plant = $plant_info['price'];

            echo "   -> 🌱 Aksi Tanam: Menganalisis " . $num_empty_plots . " lahan kosong...\n";

            foreach($empty_plots as $plot_info) {
                $slot = $plot_info['slotIndex'];
                echo "      -> Memproses Plot #$slot:\n";

                // algo pemilihan booster
                if ($plot_info['modifier'] === null) {
                    $modifier_to_use = null;

                    // pilih booster
                    if ($num_plots >= 11 && $my_ap > 2300 && ($state['inventory']['quantum-fertilizer'] ?? 0) > 0) {
                        $modifier_to_use = 'quantum-fertilizer';
                    } elseif (($state['inventory']['fertiliser'] ?? 0) > 0) {
                        $modifier_to_use = 'fertiliser';
                    }

                    if ($modifier_to_use !== null) {
                        echo "         - Memilih & menggunakan 1 " . ucfirst($booster_data[$modifier_to_use]['name']) . "...\n";
                        applyModifier($current_cookie, $modifier_to_use, $slot);
                        $state['inventory'][$modifier_to_use]--; 
                        //sleep(1);
                    }
                }

                //Tanam bibit target
                if ($target_plant === 'golden-apple') {
                    $seeds_in_inventory = $state['inventory'][$target_plant] ?? 0;
                    if ($seeds_in_inventory > 0) {
                        echo "         - Menanam Golden Apple dari inventori (sisa: " . ($seeds_in_inventory - 1) . ").\n";
                        plantSeed($current_cookie, $target_plant, $slot);
                        $state['inventory'][$target_plant]--;
                        //sleep(1);
                    } else {
                        if ($my_ap >= $cost_per_plant) {
                            echo "         - Inventori kosong. Membeli & menanam 1 Golden Apple...\n";
                            if(buySeed($current_cookie, $target_plant, 1)) {
                                plantSeed($current_cookie, $target_plant, $slot);
                                $my_ap -= $cost_per_plant;
                                //sleep(1);
                            }
                        } else {
                            echo "         - ℹ️  Inventori habis & AP tidak cukup untuk membeli. Berhenti.\n"; break;
                        }
                    }
                } else { // Tanam weat
                    if ($my_coins >= $cost_per_plant) {
                        if (buySeed($current_cookie, $target_plant, 1)) {
                            plantSeed($current_cookie, $target_plant, $slot);
                            $my_coins -= $cost_per_plant;
                            //sleep(1);
                        }
                    } else {
                        echo "         - ℹ️  Coins tidak cukup untuk membeli Wheat. Berhenti.\n"; break;
                    }
                }
            }
        } else {
            echo "   -> 💤 Tidak ada lahan kosong untuk ditanami.\n";
        }

        $waktu_panen_terdekat = null;
        
        $final_state = getGameState($current_cookie);
        if ($final_state && isset($final_state['plots'])) {
            foreach ($final_state['plots'] as $plot) {
                if ($plot['seed'] !== null) {
                    $waktu_panen_plot = strtotime($plot['seed']['endsAt']);
                    if ($waktu_panen_terdekat === null || $waktu_panen_plot < $waktu_panen_terdekat) {
                        $waktu_panen_terdekat = $waktu_panen_plot;
                    }
                }
            }
        }

        if ($waktu_panen_terdekat !== null) {
            $detik_menunggu = $waktu_panen_terdekat - time();
            $durasi_tidur = max(0, $detik_menunggu) + $sleep_buffer; 
            echo "   -> 💤 Semua lahan terisi. Tidur selama " . round($durasi_tidur / 60, 1) . " menit sampai panen berikutnya...\n";
            sleep($durasi_tidur);
        } else {
            sleep($jeda_antar_akun);
        }
    }
}
?>
