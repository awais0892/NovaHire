<section class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Roles</h2>
        <button type="button" class="btn btn-outline btn-sm" @click="roles.push({ title: '', points_text: '' })">Add Role</button>
    </div>
    <template x-for="(item, index) in roles" :key="`role-${index}`">
        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div>
                <label class="label">Role Title</label>
                <input class="input" :name="`roles[${index}][title]`" x-model="item.title">
            </div>
            <div>
                <label class="label">Points (one per line)</label>
                <textarea class="input min-h-24" :name="`roles[${index}][points_text]`" x-model="item.points_text"></textarea>
            </div>
            <div class="flex justify-end">
                <button type="button" class="btn btn-outline btn-xs" @click="roles.splice(index, 1)">Remove</button>
            </div>
        </div>
    </template>
</section>
