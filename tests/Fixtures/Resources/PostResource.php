<?php

namespace Blemli\Peek\Tests\Fixtures\Resources;

use Blemli\Peek\Tests\Fixtures\Models\Post;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;
}

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;
}

class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;
}
