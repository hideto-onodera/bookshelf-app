# BookShelf ER図（基本要件）

最新Excelのシート11・12と既存マイグレーションに基づく。

```mermaid
erDiagram
    users {
        bigint id PK
        varchar name
        varchar email UK
        timestamp email_verified_at
        varchar password
        varchar remember_token
        text two_factor_secret
        text two_factor_recovery_codes
        timestamp two_factor_confirmed_at
        timestamp created_at
        timestamp updated_at
    }
    books {
        bigint id PK
        varchar title
        varchar author
        varchar isbn UK
        date published_date
        text description
        text image_url
        bigint user_id FK
        timestamp created_at
        timestamp updated_at
    }
    genres {
        bigint id PK
        varchar name UK
        timestamp created_at
        timestamp updated_at
    }
    book_genre {
        bigint id PK
        bigint book_id FK
        bigint genre_id FK
        timestamp created_at
        timestamp updated_at
    }
    reviews {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        tinyint rating
        text comment
        timestamp created_at
        timestamp updated_at
    }
    favorites {
        bigint id PK
        bigint user_id FK
        bigint book_id FK
        timestamp created_at
        timestamp updated_at
    }
    review_likes {
        bigint id PK
        bigint user_id FK
        bigint review_id FK
        timestamp created_at
        timestamp updated_at
    }
    password_reset_tokens {
        varchar email PK
        varchar token
        timestamp created_at
    }
    failed_jobs {
        bigint id PK
        varchar uuid UK
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at
    }
    personal_access_tokens {
        bigint id PK
        varchar tokenable_type
        bigint tokenable_id
        varchar name
        varchar token UK
        text abilities
        timestamp last_used_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }
    users ||--o{ books : "registered_by"
    users ||--o{ reviews : "authored_by"
    books ||--o{ reviews : "reviewed_book"
    books ||--o{ book_genre : "book_id"
    genres ||--o{ book_genre : "genre_id"
    users ||--o{ favorites : "user_id"
    books ||--o{ favorites : "book_id"
    users ||--o{ review_likes : "user_id"
    reviews ||--o{ review_likes : "review_id"
```

## 制約と注記

- `book_genre`: UNIQUE (`book_id`, `genre_id`)
- `favorites`: UNIQUE (`user_id`, `book_id`)
- `review_likes`: UNIQUE (`user_id`, `review_id`)
- `users.email`, `books.isbn`, `genres.name`, `failed_jobs.uuid`, `personal_access_tokens.token`: UNIQUE
- `books.user_id`, `reviews.user_id`, `reviews.book_id`, `book_genre.book_id`, `book_genre.genre_id`, `favorites.user_id`, `favorites.book_id`, `review_likes.user_id`, `review_likes.review_id`: 外部キー（ON DELETE CASCADE）
- `personal_access_tokens.tokenable_type` と `tokenable_id` はポリモーフィック関連用で、通常の外部キー制約ではありません。
- `password_reset_tokens.email` は主キーですが、`users.email` への外部キー制約はありません。
- Mermaid図の多重度はDB上の許容関係です。各子レコードは親1件を参照し、親には子レコード0件以上が関連します。
- NULL可否と正確なデータ型はマイグレーションを正本とし、上のMermaidには簡潔に表示しています。
- `reading_plans`、通知機能など応用要件のテーブルは含めていません。
