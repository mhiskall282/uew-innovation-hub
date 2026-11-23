# Alpine.js Skill

> **Platform**: UEW Innovation Hub Enterprise  
> **Domain**: Client-Side Interactivity & State Management

---

## 1. Role of Alpine.js
Alpine.js 3.x is used for lightweight, declarative DOM interactivity without the overhead of heavy SPA frameworks:
- Sidebar expand/collapse toggle with local storage persistence
- Modal dialogs (create partner, delete confirmation, preview document)
- Dropdown menus and notification panels
- Live search filtering and multi-select organization tags
- Interactive metrics counters (animating from 0 to target number on page load)

---

## 2. Standard Patterns

### Sidebar State Persistence
```html
<aside x-data="{ collapsed: $persist(false).as('uew_sidebar_collapsed') }" 
       :class="collapsed ? 'w-20' : 'w-64'" 
       class="transition-all duration-300 ease-in-out bg-[#0a1b4d] text-white">
  <!-- Content -->
</aside>
```

### Telemetry Counter Animation
```html
<div x-data="{ count: 0, target: {{ $totalBeneficiaries }} }" 
     x-init="let step = Math.ceil(target / 40); let timer = setInterval(() => { count = Math.min(count + step, target); if(count >= target) clearInterval(timer); }, 25)">
  <span x-text="count.toLocaleString()" class="font-mono font-bold text-2xl text-slate-900"></span>
</div>
```

---

## 3. Best Practices
- Never use Alpine.js for business security checks; always validate on the server.
- Keep Alpine components small and co-located with their Blade elements.
- Use `x-cloak` with `[x-cloak] { display: none !important; }` to prevent flashing on page load.
