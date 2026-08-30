<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleBlockType;
use App\Enums\ContentableType;
use App\Enums\ModuleType;
use App\Enums\PublishStatus;
use App\Enums\QuizKind;
use App\Enums\QuizSegmentType;
use App\Enums\ReflectionQuestionType;
use App\Enums\SimulationType;
use App\Models\ArticleBlock;
use App\Models\ArticleContent;
use App\Models\Journey;
use App\Models\Module;
use App\Models\ModulePage;
use App\Models\QuizChoiceOption;
use App\Models\QuizContent;
use App\Models\QuizQuestion;
use App\Models\QuizSegment;
use App\Models\ReflectionChecklistItem;
use App\Models\ReflectionContent;
use App\Models\ReflectionQuestion;
use App\Models\ReflectionSection;
use App\Models\SimulationContent;
use App\Models\SimulationMatchingPair;
use App\Models\SimulationOrderingStep;
use App\Models\VideoContent;
use Illuminate\Database\Seeder;

/**
 * Seeder konten sektor E-Commerce (4 journey lengkap).
 *
 * Status per 2026-08-29 (disinkronkan dari materi riil yang sudah diisi tim
 * lewat admin panel — lihat dump-jeli-202608291151.sql):
 * - Journey 1 (Kenali Hakmu sebagai Konsumen) SUDAH berisi teks materi,
 *   gambar, dan referensi asli (bukan placeholder lagi). Dua modul materi
 *   ("Mengenal Aturan Hukum Saat Belanja Online" dan "Apa yang Bisa
 *   Dipelajari dari Komik Ini?") digabung tim jadi halaman kedua (extra_pages)
 *   dari modul Video dan modul Komik, bukan modul berdiri sendiri lagi.
 *   3 soal pertama kuis Journey 1 juga sudah dapat field `explanation` asli.
 *   Refleksi Journey 1 judul/isi juga sudah final ("Mari Nilai Kesadaran
 *   Hukummu") dan menambah section checklist sikap konsumen.
 * - Journey 2, 3, 4: modul materi/infografis MASIH placeholder bertanda
 *   "[PLACEHOLDER]" — cari string ini untuk daftar bagian yang masih perlu
 *   diisi teks asli oleh tim peneliti.
 * - Seluruh soal kuis journey 2-4 (dan soal 4-5 journey 1) masih DUMMY,
 *   bukan soal final riset.
 * - Baris simulation_ordering_steps / simulation_matching_pairs journey 2-4
 *   masih 4-6 contoh yang disusun masuk akal dari deskripsi skenario game,
 *   BUKAN data final — perlu direview/diganti sesuai desain game asli.
 *   Journey 1 ordering steps sudah dapat gambar asli per langkah.
 * - Journey 4 tidak menyeed modul "Role Play" (belum ada tabel/morph type
 *   untuk simulasi chat) sesuai keputusan Faqih; modul simulasi journey 4
 *   yang diseed adalah "Game Susun Jalur Solusi: Misi Ganti Rugi Utama" (matching).
 */
class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->journeysData() as $slug => $data) {
            $journey = Journey::withoutGlobalScopes()->where('slug', $slug)->firstOrFail();

            $order = 1;
            foreach ($data['modules'] as $spec) {
                $module = Module::withoutGlobalScopes()->updateOrCreate(
                    ['journey_id' => $journey->id, 'order' => $order],
                    [
                        'type' => $spec['type'],
                        'title' => $spec['title'],
                        'description' => $spec['description'] ?? null,
                        'estimated_minutes' => $spec['minutes'],
                        'is_required' => true,
                        'status' => PublishStatus::Published,
                    ]
                );

                $this->attachContent($module, 1, $spec['content']);

                $pageOrder = 2;
                foreach ($spec['extra_pages'] ?? [] as $extraContent) {
                    $this->attachContent($module, $pageOrder, $extraContent);
                    $pageOrder++;
                }

                $order++;
            }
        }
    }

    /**
     * @param  array{kind: string}  $content
     */
    private function attachContent(Module $module, int $pageOrder, array $content): void
    {
        $contentable = match ($content['kind']) {
            'article' => $this->buildArticle($content),
            'video' => $this->buildVideo($content),
            'quiz' => $this->buildQuiz($module, $content),
            'simulation_ordering' => $this->buildSimulationOrdering($content),
            'simulation_matching' => $this->buildSimulationMatching($content),
            'reflection' => $this->buildReflection($content),
        };

        ModulePage::updateOrCreate(
            ['module_id' => $module->id, 'order' => $pageOrder],
            [
                'contentable_type' => ContentableType::from($content['kind'] === 'article' ? 'article'
                    : ($content['kind'] === 'video' ? 'video'
                    : ($content['kind'] === 'quiz' ? 'quiz'
                    : (str_starts_with($content['kind'], 'simulation') ? 'simulation' : 'reflection'))))->value,
                'contentable_id' => $contentable->id,
            ]
        );
    }

    /**
     * @param  array{title: string, blocks: array<int, array{type: ArticleBlockType, text?: ?string, image_url?: ?string, alt_text?: ?string}>}  $content
     */
    private function buildArticle(array $content): ArticleContent
    {
        $article = ArticleContent::query()->updateOrCreate(
            ['title' => $content['title']],
            ['title' => $content['title']]
        );

        $order = 1;
        foreach ($content['blocks'] as $block) {
            ArticleBlock::query()->updateOrCreate(
                ['article_content_id' => $article->id, 'order' => $order],
                [
                    'block_type' => $block['type'],
                    'text_article' => $block['text'] ?? null,
                    'image_url' => $block['image_url'] ?? null,
                    'alt_text' => $block['alt_text'] ?? null,
                ]
            );
            $order++;
        }

        return $article;
    }

    /**
     * @param  array{title: string, youtube_url: string, prompt_question?: ?string}  $content
     */
    private function buildVideo(array $content): VideoContent
    {
        return VideoContent::query()->updateOrCreate(
            ['title' => $content['title']],
            [
                'youtube_url' => $content['youtube_url'],
                'prompt_question' => $content['prompt_question'] ?? null,
            ]
        );
    }

    /**
     * @param  array{title: string, questions: array<int, array{question: string, options: array<int, array{text: string, correct: bool}>}>}  $content
     */
    private function buildQuiz(Module $module, array $content): QuizContent
    {
        $quiz = QuizContent::query()->updateOrCreate(
            ['kind' => QuizKind::Quiz, 'journey_id' => $module->journey_id],
            ['sector_id' => null, 'passing_score' => 70, 'shuffle_questions' => false]
        );

        $segment = QuizSegment::query()->updateOrCreate(
            ['quiz_content_id' => $quiz->id, 'order' => 1],
            [
                'segment_type' => QuizSegmentType::MultipleChoice,
                'title' => $content['title'],
                'instruction' => 'Pilih satu jawaban yang paling tepat.',
            ]
        );

        $qOrder = 1;
        foreach ($content['questions'] as $q) {
            $question = QuizQuestion::query()->updateOrCreate(
                ['quiz_segment_id' => $segment->id, 'order' => $qOrder],
                ['question' => $q['question'], 'explanation' => $q['explanation'] ?? null]
            );

            $oOrder = 1;
            foreach ($q['options'] as $option) {
                QuizChoiceOption::query()->updateOrCreate(
                    ['quiz_question_id' => $question->id, 'order' => $oOrder],
                    ['option_text' => $option['text'], 'is_correct' => $option['correct']]
                );
                $oOrder++;
            }

            $qOrder++;
        }

        return $quiz;
    }

    /**
     * @param  array{title: string, scenario: string, steps: array<int, string>}  $content
     */
    private function buildSimulationOrdering(array $content): SimulationContent
    {
        $simulation = SimulationContent::query()->updateOrCreate(
            ['title' => $content['title']],
            ['simulation_type' => SimulationType::Ordering, 'scenario' => $content['scenario']]
        );

        $order = 1;
        foreach ($content['steps'] as $step) {
            $step = is_array($step) ? $step : ['label' => $step];
            SimulationOrderingStep::query()->updateOrCreate(
                ['simulation_content_id' => $simulation->id, 'order' => $order],
                ['label' => $step['label'], 'image_url' => $step['image_url'] ?? null, 'correct_position' => $order]
            );
            $order++;
        }

        return $simulation;
    }

    /**
     * @param  array{title: string, scenario: string, pairs: array<int, array{left: string, right: string}>}  $content
     */
    private function buildSimulationMatching(array $content): SimulationContent
    {
        $simulation = SimulationContent::query()->updateOrCreate(
            ['title' => $content['title']],
            ['simulation_type' => SimulationType::Matching, 'scenario' => $content['scenario']]
        );

        $order = 1;
        foreach ($content['pairs'] as $pair) {
            SimulationMatchingPair::query()->updateOrCreate(
                ['simulation_content_id' => $simulation->id, 'order' => $order],
                [
                    'left_label' => $pair['left'],
                    'left_description' => null,
                    'left_image_url' => null,
                    'right_label' => $pair['right'],
                    'right_description' => null,
                    'right_image_url' => null,
                ]
            );
            $order++;
        }

        return $simulation;
    }

    /**
     * @param  array{title: string, opening: string, sections: array<int, array{title: string, instruction: ?string, questions: array<int, string>}>}  $content
     */
    private function buildReflection(array $content): ReflectionContent
    {
        $reflection = ReflectionContent::query()->updateOrCreate(
            ['title' => $content['title']],
            [
                'opening_message' => $content['opening'],
                'closing_title' => $content['closing_title'] ?? null,
                'closing_message' => $content['closing_message'] ?? null,
            ]
        );

        $sOrder = 1;
        foreach ($content['sections'] as $section) {
            $sectionModel = ReflectionSection::query()->updateOrCreate(
                ['reflection_content_id' => $reflection->id, 'order' => $sOrder],
                ['title' => $section['title'], 'instruction' => $section['instruction'] ?? null]
            );

            $qOrder = 1;
            foreach ($section['questions'] as $question) {
                if (is_array($question)) {
                    $questionModel = ReflectionQuestion::query()->updateOrCreate(
                        ['reflection_section_id' => $sectionModel->id, 'order' => $qOrder],
                        ['question_type' => ReflectionQuestionType::Checklist, 'question_text' => $question['text']]
                    );

                    $iOrder = 1;
                    foreach ($question['items'] as $item) {
                        ReflectionChecklistItem::query()->updateOrCreate(
                            ['reflection_question_id' => $questionModel->id, 'order' => $iOrder],
                            ['label' => $item]
                        );
                        $iOrder++;
                    }
                } else {
                    ReflectionQuestion::query()->updateOrCreate(
                        ['reflection_section_id' => $sectionModel->id, 'order' => $qOrder],
                        ['question_type' => ReflectionQuestionType::OpenQuestion, 'question_text' => $question]
                    );
                }
                $qOrder++;
            }

            $sOrder++;
        }

        return $reflection;
    }

    /**
     * @return array<string, array{modules: array<int, array<string, mixed>>}>
     */
    private function journeysData(): array
    {
        $placeholder = '[PLACEHOLDER] Konten lengkap belum tersedia dari materi riset — perlu diisi manual oleh tim peneliti.';

        return [
            'kenali-hakmu-sebagai-konsumen' => ['modules' => [
                [
                    'type' => ModuleType::Opening, 'title' => 'Pembuka Journey', 'minutes' => 2,
                    'content' => ['kind' => 'article', 'title' => 'Opening Journey: Kenali Hakmu sebagai Konsumen', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Pengantar tentang kepraktisan belanja online, risiko dasar e-commerce, serta pengenalan awal mengenai peran konsumen, asas perlindungan hukum, dan keseimbangan hak-kewajiban pembeli.'],
                    ]],
                ],
                [
                    'type' => ModuleType::Video, 'title' => 'Pentingnya Perlindungan Konsumen dalam E-Commerce', 'minutes' => 10,
                    'content' => ['kind' => 'video', 'title' => 'Pentingnya Perlindungan Konsumen dalam E-Commerce', 'youtube_url' => 'https://youtu.be/BCGj0uJoujI?si=7Wfj-TgubtSEN2MP'],
                    // Materi "Mengenal Aturan Hukum Saat Belanja Online" digabung jadi page 2 modul ini
                    // (modul standalone-nya dihapus tim di admin panel setelah konten riilnya masuk).
                    'extra_pages' => [
                        ['kind' => 'article', 'title' => 'Mengenal Aturan Hukum Saat Belanja Online', 'blocks' => [
                            ['type' => ArticleBlockType::Paragraph, 'text' => "Belanja online memang memberikan banyak kemudahan, tetapi sistem ini juga memiliki risiko nyata dalam kehidupan sehari-hari. Kita semua bisa saja mengalami masalah kurang menyenangkan seperti menerima barang rusak, paket tidak sesuai dengan foto iklan, atau bahkan terkena penipuan. Itulah mengapa negara kita membuat aturan hukum yang kuat, salah satunya melalui Undang-Undang Perlindungan Konsumen untuk menjamin agar kita bisa merasa aman, nyaman, dan tenang saat bertransaksi lewat internet.\n\nMenjaga keamanan saat berbelanja online sebenarnya bukan hanya menjadi tugas pemerintah atau pihak aplikasi saja. Kita sebagai pembeli justru memiliki peran yang paling utama untuk melindungi diri sendiri. Memahami aturan hukum dasar ini adalah langkah awal yang sangat baik agar kita bisa menjadi pembeli yang lebih cerdas, tahu cara mengamankan uang kita, dan tidak mudah dirugikan oleh penjual yang nakal.\n"],
                            ['type' => ArticleBlockType::Reference, 'text' => 'IPB TV. 2022. IPB Podcast: Perlindungan Konsumen di Bidang E-          Commerce. Tautan Video: youtu.be.'],
                        ]],
                    ],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Kenali Peran Penting Kita sebagai Konsumen di Era Digital', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Kenali Peran Penting Kita sebagai Konsumen di Era Digital', 'blocks' => [
                        ['type' => ArticleBlockType::Image, 'image_url' => 'articles/blocks/01M11SBW70VGE48K18D443HVHV.png', 'alt_text' => 'Microlearning 1'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => "Pernahkah kamu membeli pakaian di marketplace, memesan makanan lewat aplikasi, atau berlangganan layanan digital? Jika pernah, berarti kamu adalah konsumen. Menurut Pasal 1 angka 2 Undang-Undang Nomor 8 Tahun 1999, konsumen adalah setiap orang yang menggunakan barang atau jasa untuk memenuhi kebutuhan diri sendiri atau keluarga, dan tidak untuk dijual kembali. Kita semua berinteraksi dengan penjual setiap hari, sehingga penting bagi kita untuk memahami peran ini agar bisa bertransaksi dengan lebih aman dan bertanggung jawab.\n\nNamun, belanja online tidak selalu mulus. Kita bisa saja menerima produk rusak atau tergiur barang yang tidak sesuai foto iklan. Di sinilah pentingnya Perlindungan Konsumen. Berdasarkan pasal 1 angka 1 di undang-undang yang sama, perlindungan konsumen adalah segala upaya untuk memberikan kepastian hukum agar kita merasa aman, nyaman, dan tidak dirugikan. Aturan ini ada untuk mencegah kita dari kerugian sejak awal dengan cara memaksa penjual memberikan informasi jujur dan pelayanan yang adil.\n\nTahukah kamu bahwa kamu tidak harus selalu membeli barang dengan uang sendiri untuk disebut sebagai konsumen yang dilindungi hukum? Jika kamu menerima hadiah baju dari teman lalu kamu memakainya untuk keperluan pribadi, para ahli hukum menjelaskan bahwa kamu tetap sah dikategorikan sebagai konsumen akhir. Memahami siapa diri kita dan bagaimana hukum melindungi kita adalah langkah awal yang paling penting sebelum kita mempelajari hak serta kewajiban kita saat berbelanja.\n"],
                        ['type' => ArticleBlockType::Reference, 'text' => "Indonesia. 1999. Undang-Undang No. 8 Tahun 1999 tentang Perlindungan Konsumen. Jakarta: Sekretariat Negara.\nMiru A, Yodo S. 2011. Hukum Perlindungan Konsumen. Jakarta: Rajawali Pers.\nShidarta. 2006. Hukum Perlindungan Konsumen Indonesia. Jakarta: Grasindo."],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Memahami Keseimbangan Hak dan Kewajiban Konsumen Cerdas', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Memahami Keseimbangan Hak dan Kewajiban Konsumen Cerdas', 'blocks' => [
                        ['type' => ArticleBlockType::Image, 'image_url' => 'articles/blocks/01M11SKYEDTVFS2HPQAZN9NS0A.png', 'alt_text' => 'Microlearning 2'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => "Saat membeli barang atau menggunakan jasa di e-commerce, kamu memiliki hak sekaligus kewajiban yang diatur resmi dalam Pasal 4 dan Pasal 5 Undang-Undang Nomor 8 Tahun 1999 tentang Perlindungan Konsumen. Kedua hal ini wajib berjalan beriringan agar ekosistem belanja digital tetap aman dan adil bagi pembeli maupun penjual.\nSebagai konsumen, kamu memiliki hak utama untuk mendapatkan informasi yang benar, jelas, dan jujur mengenai spesifikasi hingga garansi produk sebelum membeli. Kamu juga berhak menerima barang yang sesuai dengan deskripsi foto iklan, menyampaikan keluhan jika pelayanan buruk, serta memperoleh ganti rugi berupa pengembalian dana atau barang baru apabila produk yang diterima rusak akibat kelalaian penjual.\nNamun, untuk mendapatkan hak tersebut, kamu wajib melaksanakan kewajiban sebagai konsumen yang bertanggung jawab. Kewajiban pertamamu adalah membaca deskripsi produk dengan teliti sebelum checkout agar terhindar dari salah beli. Kamu juga wajib mengikuti prosedur transaksi resmi dan menggunakan metode pembayaran sah di aplikasi guna menghindari modus penipuan. Terakhir, periksalah paket saat tiba, lalu berikan ulasan yang jujur atau sampaikan komplain menggunakan bahasa yang sopan jika ada masalah.\nTahukah kamu bahwa konsumen yang tertib menjalankan kewajibannya akan memiliki posisi hukum yang jauh lebih kuat? Ketika kamu mengikuti prosedur aplikasi dan memiliki bukti transaksi yang sah, platform e-commerce akan jauh lebih mudah untuk membantumu memproses tuntutan ganti rugi terhadap penjual yang curang.\n"],
                        ['type' => ArticleBlockType::Reference, 'text' => "Indonesia. 1999. Undang-Undang No. 8 Tahun 1999 tentang Perlindungan Konsumen. Jakarta: Sekretariat Negara.\nMiru A, Yodo S. 2011. Hukum Perlindungan Konsumen. Jakarta: Rajawali Pers.\n"],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Langkah Bijak dan Cerdas Sebelum Membeli Produk', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Langkah Bijak dan Cerdas Sebelum Membeli Produk', 'blocks' => [
                        ['type' => ArticleBlockType::Image, 'image_url' => 'articles/blocks/01M11SPBN150QB4KYSWAJGPVA0.png', 'alt_text' => 'Microlearning 3'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => "Sebelum membeli produk di e-commerce, jangan terburu-buru menekan tombol checkout. Luangkan waktu beberapa menit untuk memastikan produk dan toko yang dipilih benar-benar sesuai dengan kebutuhanmu. Kebiasaan sederhana ini sangat ampuh dalam mengurangi risiko penipuan, barang tidak sesuai pesanan, atau rasa kecewa setelah berbelanja online.\nLangkah pertama yang paling krusial adalah membaca deskripsi produk dengan teliti mengenai ukuran, bahan, spesifikasi, dan ketentuan garansi. Langkah kedua, periksa reputasi toko dengan melihat rating keseluruhan, jumlah transaksi sukses, serta ulasan nyata dari pembeli lain. Setelah itu, langkah ketiga adalah membandingkan produk dan harga dari beberapa toko serupa agar kamu mendapatkan kualitas terbaik yang paling ramah di kantong.\nLangkah keempat, selalu pilih metode pembayaran resmi yang disediakan oleh platform e-commerce dan hindari transfer uang langsung ke rekening pribadi penjual di luar aplikasi. Terakhir, langkah kelima adalah selalu menyimpan bukti transaksimu dengan baik, seperti invoice digital dan riwayat percakapan dengan penjual, sebagai bukti sah jika di kemudian hari kamu perlu mengajukan komplain atau meminta pengembalian dana.\nTahukah kamu bahwa tidak semua produk dengan harga paling murah merupakan pilihan yang terbaik di dunia digital? Terkadang, harga yang terlampau jauh di bawah pasaran justru menjadi indikasi awal dari barang tiruan atau kualitas yang buruk. Oleh karena itu, membandingkan informasi produk dan ulasan pembeli lain secara objektif akan sangat membantumu membuat keputusan belanja yang tepat dan aman.\n"],
                        ['type' => ArticleBlockType::Reference, 'text' => "Indonesia. 2019. Peraturan Pemerintah Nomor 80 Tahun 2019 tentang Perdagangan Melalui Sistem Elektronik. Jakarta: Sekretariat Negara [Indonesia. 2019].\nNugroho AZ. 2018. Panduan Menjadi Konsumen Cerdas di Era Digital. Jakarta: Direktorat Jenderal Perlindungan Konsumen dan Tertib Niaga, Kementerian Perdagangan RI [Nugroho AZ. 2018]."],
                    ]],
                ],
                [
                    'type' => ModuleType::Infografis, 'title' => 'Kenali Hakmu sebagai Konsumen', 'minutes' => 3,
                    'content' => ['kind' => 'article', 'title' => 'Infografis: Kenali Hakmu sebagai Konsumen', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Belanja online memang praktis, tetapi sebagai konsumen kamu juga perlu mengetahui hak dan kewajibanmu agar setiap transaksi tetap aman dan nyaman. Yuk, pelajari informasi penting seputar hak konsumen, kewajiban konsumen, tips berbelanja yang aman, hingga langkah yang dapat dilakukan jika mengalami kerugian.'],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'articles/blocks/01M11SQS8QGN67PQG8CJWDB4GY.png', 'alt_text' => 'Infografis Kenali Hakmu sebagai Konsumen'],
                    ]],
                ],
                [
                    'type' => ModuleType::Komik, 'title' => 'Yuk, Belajar Bareng!', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Komik: Yuk, Belajar Bareng!', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Pernah menerima barang yang tidak sesuai? Atau bingung harus berbuat apa ketika pesanan bermasalah? Temukan jawabannya melalui cerita komik yang ringan, seru, dan dekat dengan pengalaman sehari-hari.'],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'articles/blocks/01M11SS4K8M9KJQ9Y40XCRC948.png', 'alt_text' => 'Komik Yuk, Belajar Bareng!'],
                    ]],
                    // "Apa yang Bisa Dipelajari dari Komik Ini?" digabung jadi page 2 modul ini
                    // (modul standalone-nya dihapus tim di admin panel; teks sama seperti semula).
                    'extra_pages' => [
                        ['kind' => 'article', 'title' => 'Pembahasan Komik J1: Apa yang Bisa Dipelajari dari Komik Ini?', 'blocks' => [
                            ['type' => ArticleBlockType::Paragraph, 'text' => 'Komik ini menggambarkan situasi yang sering dihadapi konsumen saat berbelanja melalui e-commerce. Melalui cerita tersebut, pengguna diajak memahami bahwa setiap konsumen memiliki hak untuk memperoleh produk dan layanan yang sesuai, sekaligus memiliki kewajiban untuk bertransaksi secara bijak dan bertanggung jawab.'],
                            ['type' => ArticleBlockType::Paragraph, 'text' => 'Selain itu, komik ini menunjukkan pentingnya membaca informasi produk, memeriksa reputasi penjual, serta menyimpan bukti transaksi sebagai langkah pencegahan apabila terjadi permasalahan. Ketika mengalami kerugian, konsumen juga berhak menyampaikan keluhan dan memperoleh penyelesaian sesuai dengan ketentuan yang berlaku.'],
                            ['type' => ArticleBlockType::Paragraph, 'text' => 'Pesan Utama: Perlindungan konsumen tidak hanya bergantung pada aturan, tetapi juga dimulai dari kesadaran setiap individu.'],
                            ['type' => ArticleBlockType::ListItem, 'text' => 'Memahami hak dan kewajiban sebagai konsumen.'],
                            ['type' => ArticleBlockType::ListItem, 'text' => 'Menerapkan langkah-langkah berbelanja yang aman.'],
                            ['type' => ArticleBlockType::ListItem, 'text' => 'Mengetahui tindakan yang tepat saat mengalami kerugian.'],
                            ['type' => ArticleBlockType::ListItem, 'text' => 'Menjadi konsumen yang cerdas, bijak, dan bertanggung jawab.'],
                        ]],
                    ],
                ],
                [
                    'type' => ModuleType::Simulasi, 'title' => 'Game Pilah Cepat: Keranjang Belanja Berdaya', 'minutes' => 8,
                    'content' => ['kind' => 'simulation_ordering', 'title' => 'Game Pilah Cepat: Keranjang Belanja Berdaya',
                        'scenario' => 'Susun urutan langkah yang benar seorang konsumen cerdas sebelum, saat, dan setelah membeli produk di e-commerce agar transaksi tetap aman.',
                        'steps' => [
                            ['label' => 'Baca deskripsi produk dan syarat penjual dengan teliti', 'image_url' => 'simulations/ordering/01M11TTKJXZXVK46ZD87Z75R2Y.png'],
                            ['label' => 'Periksa reputasi dan ulasan toko', 'image_url' => 'simulations/ordering/01M11TTMNJ1KENQMX8K11YCCAN.png'],
                            ['label' => 'Bandingkan harga dan metode pembayaran yang aman', 'image_url' => 'simulations/ordering/01M11TTNQ8MK66FT3YHTCWBCC4.png'],
                            ['label' => 'Lakukan checkout dan simpan bukti transaksi', 'image_url' => 'simulations/ordering/01M11TTPSSCDJC54SAZ5AFMQK1.png'],
                            ['label' => 'Periksa barang saat diterima dan laporkan bila tidak sesuai', 'image_url' => 'simulations/ordering/01M11TTQVY8VMW1GBRV9RWBPCD.png'],
                        ],
                    ],
                ],
                [
                    'type' => ModuleType::Kuis, 'title' => 'Kuis Evaluasi Journey 1', 'minutes' => 10,
                    'content' => ['kind' => 'quiz', 'title' => 'Kuis Evaluasi Journey 1', 'questions' => $this->dummyQuestionsJ1()],
                ],
                [
                    'type' => ModuleType::Refleksi, 'title' => 'Mari Nilai Kesadaran Hukummu', 'minutes' => 5,
                    'content' => ['kind' => 'reflection', 'title' => 'Mari Nilai Kesadaran Hukummu',
                        'opening' => "Selamat karena kamu telah menyelesaikan seluruh materi pada minggu pertama ini. Sekarang, mari kita lihat kembali seberapa paham kamu mengenai posisi konsumen di mata hukum. Lembar ini tidak mencari jawaban benar atau salah, jadi silakan isi sesuai pendapatmu sendiri.\n",
                        'closing_title' => 'Kata Penutup',
                        'closing_message' => "Terima kasih telah mengisi lembar pemahaman ini. Memahami siapa dirimu dan bagaimana aturan hukum melindungimu adalah modal awal yang paling penting agar kamu bisa bertransaksi dengan penuh percaya diri.\n",
                        'sections' => [
                            [
                                'title' => 'Pemahaman Hak Dasar',
                                'instruction' => 'Jawab dengan jujur sesuai pengalamanmu.',
                                'questions' => [
                                    'Setelah membaca materi minggu ini, mengapa posisi kita sebagai konsumen perlu dilindungi secara kuat oleh undang-undang?',
                                    "Menurut pendapatmu, mengapa hak untuk mendapatkan ganti rugi baru bisa kita tuntut setelah kita memenuhi kewajiban membaca deskripsi produk?\n",
                                ],
                            ],
                            [
                                'title' => 'Daftar Sikap Konsumen',
                                'instruction' => 'Beri tanda centang pada kebiasaan yang siap kamu terapkan mulai hari ini',
                                'questions' => [
                                    ['text' => 'Saya memahami bahwa menggunakan barang hadiah dari orang lain tetap dilindungi oleh hukum konsumen.', 'items' => ['Yes']],
                                    ['text' => 'Saya akan selalu mengingat hak saya untuk mendapatkan informasi produk yang jelas sebelum membeli.', 'items' => ['Yes']],
                                    ['text' => 'Saya berkomitmen untuk membaca informasi transaksi toko agar posisi hukum saya tetap aman.', 'items' => ['Yes']],
                                ],
                            ],
                        ],
                    ],
                ],
            ]],

            'belanja-online-dengan-lebih-cerdas' => ['modules' => [
                [
                    'type' => ModuleType::Video, 'title' => 'Cara Menjadi Konsumen Cerdas Saat Berbelanja Online', 'minutes' => 10,
                    'content' => ['kind' => 'video', 'title' => 'Cara Menjadi Konsumen Cerdas Saat Berbelanja Online', 'youtube_url' => 'https://www.youtube.com/watch?v=PLACEHOLDER_J2V1'],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Ringkasan: Cara Menjadi Konsumen Cerdas Saat Berbelanja Online', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Ringkasan: Cara Menjadi Konsumen Cerdas Saat Berbelanja Online', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Studi Kasus: Membandingkan Dua Toko', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Studi Kasus: Membandingkan Dua Toko', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Menilai Kredibilitas Toko dan Membaca Ulasan secara Kritis', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Menilai Kredibilitas Toko dan Membaca Ulasan secara Kritis', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Memilih Metode Pembayaran yang Aman dan Menghitung Biaya Transaksi', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Memilih Metode Pembayaran yang Aman dan Menghitung Biaya Transaksi', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Pentingnya Menyimpan Bukti Transaksi Digital', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Pentingnya Menyimpan Bukti Transaksi Digital', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Infografis, 'title' => 'Checklist sebelum Checkout', 'minutes' => 3,
                    'content' => ['kind' => 'article', 'title' => 'Infografis: Checklist sebelum Checkout', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'https://placehold.co/800x1200?text=Checklist+sebelum+Checkout', 'alt_text' => 'Infografis Checklist sebelum Checkout'],
                    ]],
                ],
                [
                    'type' => ModuleType::Simulasi, 'title' => 'Game Hubungkan Garis: Misi Penyelamatan Saldo', 'minutes' => 8,
                    'content' => ['kind' => 'simulation_matching', 'title' => 'Game Hubungkan Garis: Misi Penyelamatan Saldo',
                        'scenario' => 'Hubungkan setiap situasi belanja online berisiko dengan tindakan aman yang paling tepat untuk melindungi saldo dan datamu.',
                        'pairs' => [
                            ['left' => 'Toko menawarkan harga jauh di bawah pasar dan minta transfer langsung', 'right' => 'Curigai dan cek reputasi toko sebelum membayar'],
                            ['left' => 'Metode pembayaran tanpa proteksi/escrow dari platform', 'right' => 'Gunakan metode pembayaran resmi yang disediakan platform'],
                            ['left' => 'Ulasan toko didominasi bintang 5 tanpa foto dan komentar detail', 'right' => 'Baca ulasan secara kritis, cari ulasan dengan foto/bukti nyata'],
                            ['left' => 'Total tagihan tidak dijelaskan rincian ongkir dan pajaknya', 'right' => 'Hitung ulang total biaya sebelum menekan tombol bayar'],
                            ['left' => 'Bukti transaksi hanya berupa notifikasi yang mudah hilang', 'right' => 'Simpan tangkapan layar/bukti transaksi ke folder khusus'],
                        ],
                    ],
                ],
                [
                    'type' => ModuleType::Kuis, 'title' => 'Kuis Evaluasi Journey 2', 'minutes' => 10,
                    'content' => ['kind' => 'quiz', 'title' => 'Kuis Evaluasi Journey 2', 'questions' => $this->dummyQuestionsJ2()],
                ],
                [
                    'type' => ModuleType::Refleksi, 'title' => 'Lembar Pengawasan Toko', 'minutes' => 5,
                    'content' => ['kind' => 'reflection', 'title' => 'Lembar Pengawasan Toko',
                        'opening' => 'Coba refleksikan kebiasaanmu menilai toko sebelum berbelanja.',
                        'sections' => [[
                            'title' => 'Pengawasan Toko',
                            'instruction' => 'Jawab dengan jujur sesuai pengalamanmu.',
                            'questions' => [
                                'Hal apa yang biasanya kamu periksa dulu sebelum memutuskan membeli dari toko baru?',
                                'Pernahkah kamu hampir tertipu toko yang terlihat meyakinkan? Apa yang membuatmu sadar?',
                            ],
                        ]],
                    ],
                ],
            ]],

            'lindungi-dirimu-dari-risiko-digital' => ['modules' => [
                [
                    'type' => ModuleType::Video, 'title' => 'Risiko Transaksi Digital dan Keamanan Akun', 'minutes' => 10,
                    'content' => ['kind' => 'video', 'title' => 'Risiko Transaksi Digital dan Keamanan Akun', 'youtube_url' => 'https://www.youtube.com/watch?v=PLACEHOLDER_J3V1'],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Setelah Menonton: Risiko Transaksi Digital dan Keamanan Akun', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Setelah Menonton: Risiko Transaksi Digital dan Keamanan Akun', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Waspada Terhadap Berbagai Modus Penipuan Digital', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Waspada Terhadap Berbagai Modus Penipuan Digital', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Langkah Taktis dalam Menjaga Keamanan Akun dan Kode OTP', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Langkah Taktis dalam Menjaga Keamanan Akun dan Kode OTP', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Melindungi Privasi Data Pribadi di Ruang Siber', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Melindungi Privasi Data Pribadi di Ruang Siber', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Komik, 'title' => 'Yuk, Kenali Modus Penipuan Digital!', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Komik: Yuk, Kenali Modus Penipuan Digital!', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Belajar mengenali penipuan digital tidak harus membosankan. Ikuti kisah dalam komik ini dan temukan bagaimana cara mengenali tanda-tanda penipuan serta mengambil langkah yang tepat agar tetap aman saat bertransaksi di e-commerce.'],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'https://placehold.co/800x1200?text=Komik+Modus+Penipuan+Digital', 'alt_text' => 'Komik Yuk, Kenali Modus Penipuan Digital!'],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Apa yang Bisa Dipelajari dari Komik Ini?', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Pembahasan Komik J3: Apa yang Bisa Dipelajari dari Komik Ini?', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Komik ini menggambarkan bagaimana penipuan digital dapat terjadi melalui berbagai modus, seperti penawaran harga yang terlalu murah, ajakan bertransaksi di luar aplikasi, hingga penyalahgunaan kepercayaan konsumen. Melalui cerita ini, pengguna diajak untuk lebih waspada dalam setiap transaksi online serta memahami pentingnya mengenali tanda-tanda penipuan sejak awal.'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Komik ini juga menunjukkan bahwa ketika menjadi korban penipuan, konsumen tidak perlu panik. Segera laporkan kejadian kepada layanan pelanggan platform, simpan seluruh bukti transaksi, dan hindari berkomunikasi di luar saluran resmi.'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Pesan Utama: Penipuan digital dapat menimpa siapa saja. Dengan tetap waspada, bertransaksi melalui fitur resmi platform, serta tidak mudah tergiur oleh penawaran yang tidak wajar, kamu dapat melindungi diri dari berbagai risiko kejahatan siber.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Mengenali ciri-ciri penipuan digital dalam transaksi online.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menghindari transaksi di luar platform e-commerce.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menyimpan bukti transaksi sebagai dasar apabila terjadi masalah.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Mengetahui langkah yang tepat untuk melaporkan dugaan penipuan kepada platform.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menerapkan kebiasaan berbelanja online yang aman, cerdas, dan bertanggung jawab.'],
                    ]],
                ],
                [
                    'type' => ModuleType::Infografis, 'title' => 'Ciri-ciri Penipuan Digital', 'minutes' => 3,
                    'content' => ['kind' => 'article', 'title' => 'Infografis: Ciri-ciri Penipuan Digital', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'https://placehold.co/800x1200?text=Ciri-ciri+Penipuan+Digital', 'alt_text' => 'Infografis Ciri-ciri Penipuan Digital'],
                    ]],
                ],
                [
                    'type' => ModuleType::Simulasi, 'title' => 'Game Ketuk Sinyal Bahaya: Benteng Akun Digital', 'minutes' => 8,
                    'content' => ['kind' => 'simulation_ordering', 'title' => 'Game Ketuk Sinyal Bahaya: Benteng Akun Digital',
                        'scenario' => 'Susun urutan langkah yang benar untuk mengamankan akun saat menerima sinyal bahaya, seperti permintaan kode OTP mencurigakan.',
                        'steps' => [
                            'Jangan bagikan kode OTP ke siapa pun, termasuk yang mengaku pihak platform',
                            'Periksa notifikasi login/aktivitas mencurigakan di akun',
                            'Segera ganti kata sandi akun yang dicurigai',
                            'Aktifkan verifikasi dua langkah (2FA) untuk lapisan keamanan tambahan',
                            'Laporkan aktivitas mencurigakan ke layanan pelanggan platform',
                        ],
                    ],
                ],
                [
                    'type' => ModuleType::Kuis, 'title' => 'Kuis Evaluasi Journey 3', 'minutes' => 10,
                    'content' => ['kind' => 'quiz', 'title' => 'Kuis Evaluasi Journey 3', 'questions' => $this->dummyQuestionsJ3()],
                ],
                [
                    'type' => ModuleType::Refleksi, 'title' => 'Lembar Keamanan Akun', 'minutes' => 5,
                    'content' => ['kind' => 'reflection', 'title' => 'Lembar Keamanan Akun',
                        'opening' => 'Coba refleksikan seberapa aman kebiasaan digitalmu selama ini.',
                        'sections' => [[
                            'title' => 'Keamanan Akun',
                            'instruction' => 'Jawab dengan jujur sesuai pengalamanmu.',
                            'questions' => [
                                'Apa langkah pengamanan akun yang sudah kamu terapkan, dan apa yang belum?',
                                'Bagaimana caramu bereaksi jika ada pihak yang meminta kode OTP-mu?',
                            ],
                        ]],
                    ],
                ],
            ]],

            'berani-memperjuangkan-hakmu' => ['modules' => [
                [
                    'type' => ModuleType::Video, 'title' => 'Pentingnya Memperjuangkan Hak sebagai Konsumen', 'minutes' => 10,
                    'content' => ['kind' => 'video', 'title' => 'Pentingnya Memperjuangkan Hak sebagai Konsumen', 'youtube_url' => 'https://www.youtube.com/watch?v=PLACEHOLDER_J4V1'],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Ringkasan: Pentingnya Memperjuangkan Hak sebagai Konsumen', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Ringkasan: Pentingnya Memperjuangkan Hak sebagai Konsumen', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Solusi Cerdas Menyampaikan Komplain kepada Penjual', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Solusi Cerdas Menyampaikan Komplain kepada Penjual', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Mengenal Prosedur Pengembalian Barang dan Dana secara Tepat', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Mengenal Prosedur Pengembalian Barang dan Dana secara Tepat', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Langkah Eskalasi Melalui Layanan Pelanggan dan Laporan Pelanggaran', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Langkah Eskalasi Melalui Layanan Pelanggan dan Laporan Pelanggaran', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                    ]],
                ],
                [
                    'type' => ModuleType::Komik, 'title' => 'Yuk, Pelajari Cara Memperjuangkan Hakmu sebagai Konsumen!', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Komik: Yuk, Pelajari Cara Memperjuangkan Hakmu sebagai Konsumen!', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Setiap konsumen memiliki hak untuk mendapatkan barang atau jasa yang sesuai dengan informasi yang dijanjikan. Melalui komik ini, kamu akan mengikuti kisah seorang konsumen yang mengalami masalah dalam transaksi online dan berhasil memperjuangkan haknya dengan langkah-langkah yang tepat.'],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'https://placehold.co/800x1200?text=Komik+Memperjuangkan+Hakmu', 'alt_text' => 'Komik Yuk, Pelajari Cara Memperjuangkan Hakmu sebagai Konsumen!'],
                    ]],
                ],
                [
                    'type' => ModuleType::Materi, 'title' => 'Apa yang Bisa Dipelajari dari Komik Ini?', 'minutes' => 5,
                    'content' => ['kind' => 'article', 'title' => 'Pembahasan Komik J4: Apa yang Bisa Dipelajari dari Komik Ini?', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Komik ini menggambarkan bahwa ketika konsumen menerima barang yang tidak sesuai, mengalami kerugian, atau mendapatkan pelayanan yang tidak semestinya, mereka tetap memiliki hak untuk menyampaikan keluhan dan memperoleh penyelesaian.'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Komik ini juga menunjukkan pentingnya menyimpan bukti transaksi, berkomunikasi dengan penjual secara sopan, memanfaatkan fitur pengaduan yang tersedia di platform e-commerce, serta memahami hak-hak konsumen sebagaimana diatur dalam peraturan perundang-undangan.'],
                        ['type' => ArticleBlockType::Paragraph, 'text' => 'Pesan Utama: Memperjuangkan hak sebagai konsumen bukan berarti mencari keuntungan, tetapi memastikan bahwa setiap transaksi berlangsung secara adil.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Mengenali hak-hak konsumen dalam transaksi e-commerce.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Mengetahui langkah yang tepat ketika menerima barang atau layanan yang tidak sesuai.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menyimpan dan memanfaatkan bukti transaksi sebagai dasar penyampaian keluhan.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menggunakan fitur pengaduan atau layanan pelanggan pada platform e-commerce untuk menyelesaikan permasalahan.'],
                        ['type' => ArticleBlockType::ListItem, 'text' => 'Menjadi konsumen yang lebih cerdas, berani memperjuangkan haknya, dan bertanggung jawab dalam setiap transaksi online.'],
                    ]],
                ],
                [
                    'type' => ModuleType::Infografis, 'title' => 'Langkah Mengajukan Komplain', 'minutes' => 3,
                    'content' => ['kind' => 'article', 'title' => 'Infografis: Langkah Mengajukan Komplain', 'blocks' => [
                        ['type' => ArticleBlockType::Paragraph, 'text' => $placeholder],
                        ['type' => ArticleBlockType::Image, 'image_url' => 'https://placehold.co/800x1200?text=Langkah+Mengajukan+Komplain', 'alt_text' => 'Infografis Langkah Mengajukan Komplain'],
                    ]],
                ],
                [
                    'type' => ModuleType::Simulasi, 'title' => 'Game Susun Jalur Solusi: Misi Ganti Rugi Utama', 'minutes' => 8,
                    'content' => ['kind' => 'simulation_matching', 'title' => 'Game Susun Jalur Solusi: Misi Ganti Rugi Utama',
                        'scenario' => 'Hubungkan setiap masalah transaksi dengan jalur penyelesaian/eskalasi yang paling tepat sesuai prosedur komplain e-commerce.',
                        'pairs' => [
                            ['left' => 'Barang yang diterima rusak/tidak sesuai deskripsi', 'right' => 'Ajukan komplain ke penjual disertai foto/video bukti'],
                            ['left' => 'Penjual tidak merespons komplain dalam batas waktu wajar', 'right' => 'Eskalasi ke fitur pengaduan/Customer Service platform'],
                            ['left' => 'Pengembalian dana (refund) belum diterima', 'right' => 'Cek status refund di halaman pesanan, lalu hubungi CS platform'],
                            ['left' => 'Penjual meminta pembatalan komplain di luar aplikasi', 'right' => 'Tolak dan tetap proses lewat jalur resmi platform'],
                            ['left' => 'Sengketa tidak selesai meski sudah eskalasi ke platform', 'right' => 'Laporkan ke lembaga perlindungan konsumen (mis. BPKN/YLKI)'],
                        ],
                    ],
                ],
                [
                    'type' => ModuleType::Kuis, 'title' => 'Kuis Evaluasi Journey 4', 'minutes' => 10,
                    'content' => ['kind' => 'quiz', 'title' => 'Kuis Evaluasi Journey 4', 'questions' => $this->dummyQuestionsJ4()],
                ],
                [
                    'type' => ModuleType::Refleksi, 'title' => 'Deklarasi Konsumen Berdaya', 'minutes' => 5,
                    'content' => ['kind' => 'reflection', 'title' => 'Deklarasi Konsumen Berdaya',
                        'opening' => 'Setelah menyelesaikan seluruh journey, coba tuliskan komitmenmu sebagai konsumen yang berdaya.',
                        'closing_title' => 'Selamat, kamu Consumer Champion!',
                        'closing_message' => 'Teruslah menjadi konsumen yang cerdas, kritis, dan berani memperjuangkan hakmu.',
                        'sections' => [[
                            'title' => 'Deklarasi Konsumen Berdaya',
                            'instruction' => 'Tuliskan komitmenmu dengan jujur.',
                            'questions' => [
                                'Apa satu hal baru yang paling berharga yang kamu pelajari dari seluruh journey ini?',
                                'Apa komitmenmu sebagai konsumen berdaya untuk transaksi online berikutnya?',
                            ],
                        ]],
                    ],
                ],
            ]],
        ];
    }

    /**
     * DUMMY — belum final, perlu direview isinya.
     *
     * @return array<int, array{question: string, options: array<int, array{text: string, correct: bool}>}>
     */
    private function dummyQuestionsJ1(): array
    {
        return [
            ['question' => 'Apa yang dimaksud dengan hak konsumen dalam transaksi e-commerce?', 'explanation' => "Konsumen adalah setiap orang yang menggunakan barang atau jasa untuk memenuhi kebutuhan diri sendiri, keluarga, atau orang lain, dan produk tersebut tidak untuk diperdagangkan kembali. Saat kamu membeli pakaian, memesan makanan, atau berlangganan layanan digital untuk kebutuhan sehari-hari, kamu sedang berperan sebagai konsumen akhir yang sah. Ingat, kamu tidak harus selalu membeli barang dengan uang sendiri untuk disebut sebagai konsumen, karena orang yang memanfaatkan barang dari hasil hadiah pemberian orang lain juga tetap dilindungi oleh hukum.\n", 'options' => [
                ['text' => 'Hak untuk selalu mendapat diskon', 'correct' => false],
                ['text' => 'Hak untuk memperoleh produk/layanan sesuai informasi yang dijanjikan', 'correct' => true],
                ['text' => 'Hak untuk membatalkan pesanan tanpa alasan setelah barang diterima', 'correct' => false],
                ['text' => 'Hak untuk memaksa penjual memberi hadiah', 'correct' => false],
            ]],
            ['question' => 'Berikut yang termasuk kewajiban konsumen saat berbelanja online adalah...', 'explanation' => "Perlindungan konsumen adalah segala upaya untuk memberikan kepastian hukum dan melindungi hak-hak kita sebagai pembeli agar merasa aman, nyaman, dan tenang saat menggunakan barang atau jasa. Kehadiran aturan ini sangat penting di dunia digital untuk menyeimbangkan posisi kita dengan pelaku usaha. Ingat, perlindungan konsumen tidak hanya berlaku ketika kita sudah mengalami masalah, tetapi juga bertujuan untuk mencegah kerugian sejak awal dengan cara memaksa penjual memberikan pelayanan yang jujur.\n", 'options' => [
                ['text' => 'Membaca informasi produk dengan teliti sebelum membeli', 'correct' => true],
                ['text' => 'Menghindari membaca syarat dan ketentuan', 'correct' => false],
                ['text' => 'Membayar tanpa memeriksa reputasi penjual', 'correct' => false],
                ['text' => 'Mengabaikan bukti transaksi', 'correct' => false],
            ]],
            ['question' => 'Langkah apa yang sebaiknya dilakukan sebelum membeli produk secara online?', 'explanation' => "Sebagai konsumen, kamu memiliki hak istimewa yang dijamin oleh undang-undang untuk mendapatkan informasi yang benar, jelas, dan jujur mengenai kondisi dan jaminan barang yang akan kamu beli. Informasi yang lengkap mengenai spesifikasi, ukuran, hingga ketentuan garansi akan sangat membantumu dalam mengambil keputusan yang tepat. Ingat, hak untuk mendapatkan informasi jujur ini adalah modal awal kita agar terhindar dari rasa kecewa atau merasa tertipu oleh foto iklan.\n", 'options' => [
                ['text' => 'Langsung transfer tanpa cek apa pun', 'correct' => false],
                ['text' => 'Memeriksa reputasi penjual dan membaca deskripsi produk', 'correct' => true],
                ['text' => 'Mengabaikan ulasan pembeli lain', 'correct' => false],
                ['text' => 'Membeli dari toko dengan harga termurah saja', 'correct' => false],
            ]],
            ['question' => 'Apa gunanya menyimpan bukti transaksi digital?', 'options' => [
                ['text' => 'Tidak ada gunanya', 'correct' => false],
                ['text' => 'Sebagai dasar pengajuan komplain jika terjadi masalah', 'correct' => true],
                ['text' => 'Hanya untuk koleksi pribadi', 'correct' => false],
                ['text' => 'Supaya toko memberikan diskon tambahan', 'correct' => false],
            ]],
            ['question' => 'Jika kamu menerima barang yang tidak sesuai pesanan, tindakan pertama yang tepat adalah...', 'options' => [
                ['text' => 'Diam saja karena sudah terlanjur bayar', 'correct' => false],
                ['text' => 'Menyampaikan keluhan ke penjual/platform disertai bukti', 'correct' => true],
                ['text' => 'Memberi ulasan buruk tanpa menghubungi penjual', 'correct' => false],
                ['text' => 'Langsung memblokir penjual tanpa komplain', 'correct' => false],
            ]],
        ];
    }

    /**
     * DUMMY — belum final, perlu direview isinya.
     *
     * @return array<int, array{question: string, options: array<int, array{text: string, correct: bool}>}>
     */
    private function dummyQuestionsJ2(): array
    {
        return [
            ['question' => 'Ciri toko online yang kredibel biasanya ditunjukkan dengan...', 'options' => [
                ['text' => 'Ulasan pembeli yang detail dan disertai foto nyata', 'correct' => true],
                ['text' => 'Harga jauh di bawah pasar tanpa alasan jelas', 'correct' => false],
                ['text' => 'Tidak memiliki riwayat transaksi sama sekali', 'correct' => false],
                ['text' => 'Meminta pembayaran di luar platform', 'correct' => false],
            ]],
            ['question' => 'Mengapa sebaiknya menggunakan metode pembayaran resmi dari platform e-commerce?', 'options' => [
                ['text' => 'Karena lebih ribet', 'correct' => false],
                ['text' => 'Karena ada perlindungan/escrow jika terjadi masalah', 'correct' => true],
                ['text' => 'Karena selalu lebih mahal', 'correct' => false],
                ['text' => 'Tidak ada alasan khusus', 'correct' => false],
            ]],
            ['question' => 'Sebelum checkout, sebaiknya konsumen menghitung ulang...', 'options' => [
                ['text' => 'Total harga barang, ongkir, dan biaya tambahan lainnya', 'correct' => true],
                ['text' => 'Jumlah pengikut toko', 'correct' => false],
                ['text' => 'Jumlah produk yang terjual', 'correct' => false],
                ['text' => 'Warna tampilan aplikasi', 'correct' => false],
            ]],
            ['question' => 'Apa risiko utama jika bertransaksi di luar platform resmi e-commerce?', 'options' => [
                ['text' => 'Tidak ada risiko', 'correct' => false],
                ['text' => 'Kehilangan perlindungan platform jika terjadi penipuan', 'correct' => true],
                ['text' => 'Mendapat diskon otomatis', 'correct' => false],
                ['text' => 'Barang pasti lebih cepat sampai', 'correct' => false],
            ]],
            ['question' => 'Bukti transaksi digital sebaiknya disimpan sampai...', 'options' => [
                ['text' => 'Langsung dihapus setelah checkout', 'correct' => false],
                ['text' => 'Barang diterima dan tidak ada sengketa lagi', 'correct' => true],
                ['text' => 'Hanya sampai pembayaran berhasil', 'correct' => false],
                ['text' => 'Tidak perlu disimpan sama sekali', 'correct' => false],
            ]],
        ];
    }

    /**
     * DUMMY — belum final, perlu direview isinya.
     *
     * @return array<int, array{question: string, options: array<int, array{text: string, correct: bool}>}>
     */
    private function dummyQuestionsJ3(): array
    {
        return [
            ['question' => 'Salah satu ciri umum penipuan digital adalah...', 'options' => [
                ['text' => 'Harga wajar sesuai pasar', 'correct' => false],
                ['text' => 'Penawaran harga yang jauh lebih murah dari wajar', 'correct' => true],
                ['text' => 'Toko memiliki banyak ulasan detail', 'correct' => false],
                ['text' => 'Transaksi selalu lewat platform resmi', 'correct' => false],
            ]],
            ['question' => 'Kode OTP sebaiknya...', 'options' => [
                ['text' => 'Dibagikan ke siapa saja yang memintanya', 'correct' => false],
                ['text' => 'Tidak pernah dibagikan ke pihak lain, termasuk yang mengaku CS', 'correct' => true],
                ['text' => 'Diposting di media sosial', 'correct' => false],
                ['text' => 'Diberikan ke penjual agar transaksi cepat', 'correct' => false],
            ]],
            ['question' => 'Jika ada pesan mengaku dari CS platform meminta OTP, sebaiknya...', 'options' => [
                ['text' => 'Diberikan karena mengaku pihak resmi', 'correct' => false],
                ['text' => 'Diabaikan/ditolak dan dilaporkan sebagai upaya penipuan', 'correct' => true],
                ['text' => 'Dibalas dengan data pribadi lain', 'correct' => false],
                ['text' => 'Diteruskan ke teman', 'correct' => false],
            ]],
            ['question' => 'Langkah menjaga privasi data pribadi di ruang siber antara lain...', 'options' => [
                ['text' => 'Membagikan data pribadi di form yang tidak jelas asal-usulnya', 'correct' => false],
                ['text' => 'Membatasi informasi pribadi yang dibagikan dan memakai kata sandi kuat', 'correct' => true],
                ['text' => 'Menggunakan kata sandi yang sama di semua akun', 'correct' => false],
                ['text' => 'Mematikan semua pengaturan keamanan akun', 'correct' => false],
            ]],
            ['question' => 'Jika sudah menjadi korban penipuan digital, langkah tepat adalah...', 'options' => [
                ['text' => 'Diam saja agar tidak malu', 'correct' => false],
                ['text' => 'Melaporkan ke layanan pelanggan platform dan menyimpan bukti', 'correct' => true],
                ['text' => 'Menghapus semua bukti transaksi', 'correct' => false],
                ['text' => 'Melanjutkan komunikasi di luar platform dengan pelaku', 'correct' => false],
            ]],
        ];
    }

    /**
     * DUMMY — belum final, perlu direview isinya.
     *
     * @return array<int, array{question: string, options: array<int, array{text: string, correct: bool}>}>
     */
    private function dummyQuestionsJ4(): array
    {
        return [
            ['question' => 'Ketika menerima barang tidak sesuai pesanan, langkah pertama yang tepat adalah...', 'options' => [
                ['text' => 'Menyampaikan komplain ke penjual secara sopan disertai bukti', 'correct' => true],
                ['text' => 'Langsung memberi ulasan bintang satu tanpa komplain', 'correct' => false],
                ['text' => 'Membiarkan saja', 'correct' => false],
                ['text' => 'Mengancam penjual di media sosial', 'correct' => false],
            ]],
            ['question' => 'Prosedur pengembalian dana (refund) yang tepat biasanya dilakukan melalui...', 'options' => [
                ['text' => 'Transfer pribadi ke rekening penjual', 'correct' => false],
                ['text' => 'Fitur pengajuan refund resmi di platform e-commerce', 'correct' => true],
                ['text' => 'Menyebarkan keluhan di grup chat', 'correct' => false],
                ['text' => 'Tidak perlu prosedur apa pun', 'correct' => false],
            ]],
            ['question' => 'Jika penjual tidak merespons komplain dalam waktu wajar, konsumen sebaiknya...', 'options' => [
                ['text' => 'Menyerah dan tidak melakukan apa pun', 'correct' => false],
                ['text' => 'Melakukan eskalasi lewat layanan pelanggan platform', 'correct' => true],
                ['text' => 'Membeli lagi di toko yang sama', 'correct' => false],
                ['text' => 'Menghapus akun sendiri', 'correct' => false],
            ]],
            ['question' => 'Bukti apa yang penting disiapkan saat mengajukan komplain?', 'options' => [
                ['text' => 'Tidak perlu bukti apa pun', 'correct' => false],
                ['text' => 'Foto/video produk dan riwayat percakapan/transaksi', 'correct' => true],
                ['text' => 'Cukup mengingat-ingat saja', 'correct' => false],
                ['text' => 'Testimoni pembeli lain', 'correct' => false],
            ]],
            ['question' => 'Jika sengketa tidak selesai meski sudah melalui platform, langkah lanjutan yang tepat adalah...', 'options' => [
                ['text' => 'Berhenti berbelanja online selamanya', 'correct' => false],
                ['text' => 'Melaporkan ke lembaga perlindungan konsumen (mis. BPKN/YLKI)', 'correct' => true],
                ['text' => 'Menyebarkan data pribadi penjual', 'correct' => false],
                ['text' => 'Mengabaikan kerugian yang dialami', 'correct' => false],
            ]],
        ];
    }
}
