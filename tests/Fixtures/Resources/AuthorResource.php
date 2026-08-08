<?php

namespace Blemli\Peek\Tests\Fixtures\Resources;

use Blemli\Peek\Tests\Fixtures\Models\Author;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;

// Edit page only — no view page, so peek links must fall back to edit.
class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPages(): array
    {
        return [
            'index' => ListAuthors::route('/'),
            'edit' => EditAuthor::route('/{record}/edit'),
        ];
    }
}

class ListAuthors extends ListRecords
{
    protected static string $resource = AuthorResource::class;
}

class EditAuthor extends EditRecord
{
    protected static string $resource = AuthorResource::class;
}
