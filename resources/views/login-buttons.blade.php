{{-- 
    フェデレーション認証ボタン群を表示するビュー
    @param array $actions Filament Action の配列
--}}
@if (!empty($actions))
    <div class="space-y-3 mb-6">
        @foreach($actions as $action)
            {{ $action->render() }}
        @endforeach
    </div>
@endif