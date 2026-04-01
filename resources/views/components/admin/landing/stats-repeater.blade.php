<section class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Stats</h2>
        <button type="button" class="btn btn-outline btn-sm" @click="stats.push({ label: '', value: '' })">Add Stat</button>
    </div>
    <template x-for="(item, index) in stats" :key="`stat-${index}`">
        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 md:grid-cols-2 dark:border-gray-700">
            <div>
                <label class="label">Label</label>
                <input class="input" :name="`stats[${index}][label]`" x-model="item.label">
            </div>
            <div>
                <label class="label">Value</label>
                <input class="input" :name="`stats[${index}][value]`" x-model="item.value">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="button" class="btn btn-outline btn-xs" @click="stats.splice(index, 1)">Remove</button>
            </div>
        </div>
    </template>
</section>
