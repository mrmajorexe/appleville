# appleville
auto farm applevile


agoritma bot ini adalah memperioritaskan 11 plot terlebih dahulu (algo wheat+fertiliser), setelah tercapai bot akan memprioritaskan penukaran ap exchange untuk mendapatkan kurang lebih 2300 AP, ketika jumlah ap lebih dari atau sama dengan 2300, bot akan melalukan farming AP dengan golden apple+quantum fertiliser

strategi di bot ini sudah diatur sedemikian rupa untuk memberikan hasil paling optimal


Contoh kondisi:

1. my coins = 20
2. my plot = 1

alur 1 = bot akan menanam wheat terus menerus hingga jumlah coins dapat untuk membeli plot + 100 (sisa coins harus diatas 100, di config.php ada pada var $cadangan_coins_tetap)

alur 2 = jika alur 1 sudah terpenuhi & harga plot berikutnya harus dibayar dengan AP, bot akan menanam wheat dan menukarnya ke AP hingga semua AP exchange limit, alur 2 bertujuan untuk mengumpulkan AP yang digunakan untuk upgrade ke plot berikutnya. Alur 1 dan 2 dilakukan terus menerus hingga total tercapai 11 plot

alur 3 = jika alur 1, 2 tercapai dan jumlah AP lebih dari 2300 dan AP exchange limit, bot akan melakukan farming AP menggunakan golden apple + quantum vertilizer secara otomatis ---- hingga AP exchange teriset kembali



catatan!!!
Kamu harus membeli booster secara manual (wajib menggunakan booster),minimal harus ada 11 booster di inventori
jika algo bot masih menanam wheat, belilah fertilizser
jika algo bot menanam golden apple, belilah quantum vertilizer
