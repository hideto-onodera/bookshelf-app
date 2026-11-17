<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            throw new RuntimeException(
                '書籍登録用のユーザーが存在しません。'
            );
        }

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'description' => '猫の視点から人間社会を風刺的に描いた日本文学の名作。',
                'genres' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'description' => '人間関係を円滑にするための考え方と実践方法を紹介する一冊。',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'description' => '読みやすく理解しやすいコードを書くための技術を解説した書籍。',
                'genres' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'description' => '主体性や目標設定など、人生と仕事に役立つ習慣を解説した書籍。',
                'genres' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'description' => '正義感の強い青年教師が赴任先で経験する騒動を描いた小説。',
                'genres' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'description' => '人類の誕生から現代社会までの歴史を幅広い視点で考察する書籍。',
                'genres' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'description' => '保守性と可読性の高いソフトウェアを開発するための実践書。',
                'genres' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'description' => 'アドラー心理学を対話形式で紹介し、自分らしい生き方を考える書籍。',
                'genres' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'description' => '若手芸人たちの交流を通じて、才能や生き方を描いた小説。',
                'genres' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'description' => 'データに基づいて世界を正しく理解するための思考法を紹介する書籍。',
                'genres' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'description' => '海上輸送用コンテナが世界経済と物流を変革した歴史を描く書籍。',
                'genres' => ['ビジネス', '歴史'],
            ],
        ];

        foreach ($books as $index => $data) {
            $genreNames = $data['genres'];

            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'published_date' => $data['published_date'],
                    'description' => $data['description'],
                    'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text='
                        .($index + 1),
                    'user_id' => $user->id,
                ]
            );

            $genreIds = Genre::whereIn('name', $genreNames)
                ->pluck('id')
                ->toArray();

            if (count($genreIds) !== count($genreNames)) {
                throw new RuntimeException(
                    '必要なジャンルが登録されていません: '
                    .$data['title']
                );
            }

            $book->genres()->sync($genreIds);
        }
    }
}
