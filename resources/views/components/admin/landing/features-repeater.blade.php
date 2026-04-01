<section class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Features</h2>
        <button type="button" class="btn btn-outline btn-sm" @click="features.push({ icon: 'sparkles', title: '', desc: '' })">Add Feature</button>
    </div>
    <template x-for="(item, index) in features" :key="`feature-${index}`">
        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="label">Icon (Lucide name)</label>
                    <input class="input" :name="`features[${index}][icon]`" x-model="item.icon">
                </div>
                <div>
                    <label class="label">Title</label>
                    <input class="input" :name="`features[${index}][title]`" x-model="item.title">
                </div>
            </div>
            <div>
                <label class="label">Description</label>
                <textarea class="input min-h-20" :name="`features[${index}][desc]`" x-model="item.desc"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" class="btn btn-outline btn-xs" @click="features.splice(index, 1)">Remove</button>
            </div>
        </div>
    </template>
</section>
