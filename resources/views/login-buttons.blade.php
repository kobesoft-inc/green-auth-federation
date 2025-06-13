{{-- 
    フェデレーション認証ボタン群を表示するビュー
    @param array $actions Filament Action の配列
--}}
@if (!empty($actions))
    {{-- 区切り線とラベル --}}
    <div class="flex items-center my-6">
        <div class="flex-1 border-t border-gray-300"></div>
        <span class="px-3 text-sm text-gray-500">{{ __('green-auth-federation::federation.or') }}</span>
        <div class="flex-1 border-t border-gray-300"></div>
    </div>

    {{-- フェデレーション認証ボタン群 --}}
    <div class="space-y-3 mb-6">
        @foreach($actions as $action)
            <div class="w-full">
                {{ $action->extraAttributes(['class' => 'w-full justify-center'])->render() }}
            </div>
        @endforeach
    </div>
@endif