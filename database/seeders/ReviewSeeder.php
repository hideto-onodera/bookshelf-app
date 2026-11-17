<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emails = [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ];

        $users = User::whereIn('email', $emails)
            ->get()
            ->keyBy('email');

        if ($users->count() !== 5) {
            throw new RuntimeException(
                'レビュー登録に必要なユーザー5人が揃っていません。'
            );
        }

        $reviews = [
            [
                'isbn' => '9784101010014',
                'comments' => [
                    [5, '猫の視点から描かれる人間観察が面白く、何度も笑いました。'],
                    [4, '明治時代の暮らしや価値観を感じられる作品でした。'],
                    [3, '独特の文章に慣れるまで時間がかかりましたが、楽しめました。'],
                ],
            ],
            [
                'isbn' => '9784422100524',
                'comments' => [
                    [5, '相手の立場で考えることの大切さを学びました。'],
                    [4, '職場でのコミュニケーションに活用できる内容でした。'],
                    [3, '参考になる部分が多い一方、実践には継続が必要だと感じました。'],
                ],
            ],
            [
                'isbn' => '9784873115658',
                'comments' => [
                    [5, '変数名やコメントの付け方を見直すきっかけになりました。'],
                    [4, '具体的なコード例が多く、実務に取り入れやすいです。'],
                    [3, '基本的な内容もありますが、復習に役立ちました。'],
                ],
            ],
            [
                'isbn' => '9784863940246',
                'comments' => [
                    [5, '主体的に行動することの重要性を改めて実感しました。'],
                    [4, '仕事だけでなく日常生活にも応用できる考え方でした。'],
                    [3, '内容は充実していますが、実践するには時間が必要です。'],
                ],
            ],
            [
                'isbn' => '9784101010021',
                'comments' => [
                    [5, '主人公のまっすぐな性格が魅力的で、一気に読みました。'],
                    [4, '登場人物のやり取りが生き生きとしていて楽しめました。'],
                    [3, '短く読みやすい一方、時代背景の理解が必要な部分もありました。'],
                ],
            ],
            [
                'isbn' => '9784309226712',
                'comments' => [
                    [5, '人類の歴史を大きな視点から捉え直すことができました。'],
                    [4, '農業革命や社会制度についての考察が印象的でした。'],
                    [3, '興味深い内容ですが、一部の議論は慎重に読みたいと感じました。'],
                ],
            ],
            [
                'isbn' => '9784048930598',
                'comments' => [
                    [5, '保守しやすいコードを書くための考え方が身につきました。'],
                    [4, '関数の分割や命名について実践的に学べました。'],
                    [3, '参考になる一方、開発環境に応じた工夫も必要だと思いました。'],
                ],
            ],
            [
                'isbn' => '9784478025819',
                'comments' => [
                    [5, '他人の評価に振り回されない生き方について深く考えました。'],
                    [4, '対話形式なので心理学の考え方を理解しやすかったです。'],
                    [3, '刺激を受けましたが、すべてに納得できたわけではありません。'],
                ],
            ],
            [
                'isbn' => '9784163902302',
                'comments' => [
                    [5, '芸人として生きる人々の葛藤が丁寧に描かれていました。'],
                    [4, '登場人物の関係性が印象に残る作品でした。'],
                    [3, '静かな展開ですが、読み終えた後に余韻が残りました。'],
                ],
            ],
            [
                'isbn' => '9784822289607',
                'comments' => [
                    [5, '思い込みを疑い、数字で物事を見る大切さを学びました。'],
                    [4, '世界の現状について新しい発見が多くありました。'],
                    [3, '図表が分かりやすく、知識を整理するのに役立ちました。'],
                ],
            ],
            [
                'isbn' => '9784822251468',
                'comments' => [
                    [5, 'コンテナが世界の物流を変えた過程に驚かされました。'],
                    [4, '技術革新と経済の関係を具体的に理解できました。'],
                ],
            ],
        ];

        foreach ($reviews as $bookIndex => $bookData) {
            $book = Book::where('isbn', $bookData['isbn'])->first();

            if (!$book) {
                throw new RuntimeException(
                    'レビュー対象の書籍が存在しません: '
                    . $bookData['isbn']
                );
            }

            foreach ($bookData['comments'] as $reviewIndex => $data) {
                $userIndex = ($bookIndex + $reviewIndex) % 5;
                $email = $emails[$userIndex];
                $user = $users->get($email);

                Review::create([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                    'rating' => $data[0],
                    'comment' => $data[1],
                ]);
            }
        }
    }
}