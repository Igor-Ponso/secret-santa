<p align="center">
  <img 
    src="https://raw.githubusercontent.com/igor-ponso/secret-santa/main/.github/assets/banner.webp" 
    alt="Secret Santa Banner" 
    width="600"
/>
</p>


<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/build-passing-brightgreen" alt="Build Status"></a>
  <a href="#"><img src="https://img.shields.io/badge/license-MIT-blue.svg" alt="License"></a>
  <a href="#"><img src="https://img.shields.io/badge/stack-Laravel%20%2B%20Vue-red" alt="Stack"></a>
  <a href="#"><img src="https://img.shields.io/badge/tests-PEST%20%7C%20Vitest-yellow" alt="Test Suite"></a>
</p>

# Secret Santa 🎁

A modern and complete solution for organizing Secret Santa groups in a simple, fun, and secure way.

This project was built with a focus on great user experience, security, and solid fullstack development practices using the most up-to-date Laravel and Vue technologies.

---

## 🔧 Tech Stack

- [Laravel 12](https://laravel.com/docs/12.x) with [Inertia.js 2](https://inertiajs.com/)  
- [Vue.js 3](https://vuejs.org/) with Composition API + [TypeScript](https://www.typescriptlang.org/)  
- [Vite](https://vitejs.dev/)  
- [ShadCN Vue](https://vue.shadcn.dev/) (UI components, accessible by default)  
- [PEST](https://pestphp.com/) for backend testing  
- [Vitest](https://vitest.dev/) for frontend unit/integration testing  
- [MySQL](https://www.mysql.com/) as the main database  
- [GitHub Actions](https://docs.github.com/en/actions) for CI automation  



---

## 🎯 Planned Features

- [x] Authenticated users can create Secret Santa groups  
- [ ] Shareable invitation link for guests  
- [ ] Set minimum and maximum gift value  
- [ ] Draw restrictions (e.g., "X can't draw Y")  
- [ ] Public group page with countdown  
- [ ] Automatic draw with private results  
- [ ] Email notifications  
- [ ] Edit participants before drawing  
- [ ] Group history overview  

---

## 📸 Interface

> Designed to be responsive, accessible, and user-friendly, with smooth animations and a delightful UX.

---

## 🧪 Testing

- Backend tested with [PEST](https://pestphp.com)  
- Frontend unit/integration testing planned with [Vitest](https://vitest.dev)  
- CI setup using GitHub Actions to ensure code quality  

---

## 🚀 Getting Started Locally

1. Clone the repository:  
   `git clone https://github.com/igor-ponso/secret-santa.git`

2. Navigate to the project folder:  
   `cd secret-santa`

3. Install PHP dependencies:  
   `composer install`

4. Install JavaScript dependencies:  
   `bun install`  
   (or `npm install`)

5. Copy `.env.example` to `.env`:  
   `cp .env.example .env`

6. Generate the application key:  
   `php artisan key:generate`

7. Run migrations:  
   `php artisan migrate`

8. Start the Laravel development server:  
   `php artisan serve`

9. In another terminal, run Vite:  
   `bun run dev`

---

## 🙌 Contributions

Contributions are welcome!  
Feel free to open Issues or Pull Requests with improvements, bug fixes, or suggestions.

---

## 📜 License

This project is licensed under the [MIT License](LICENSE).
