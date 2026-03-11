# Reusable UI Patterns for Testimonials

## 1. Modal Components
**File:** `resources/js/Components/Modal.jsx`
- **Core API:**
  - `show` (bool): Visibility state
  - `onClose` (func): Handler for closing
  - `maxWidth` (string): 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '4xl' (default: '2xl')
  - `children` (node): Content
- **Usage Pattern:**
  - Used in `ProgramStudiModal.jsx` (lines 122-314)
  - Supports `Headless UI` transitions automatically
  - **Recommendation:** Use this existing `Modal` component directly. It handles accessibility (focus trap, escape key) and transitions.

**File:** `resources/js/Components/ProgramStudiModal.jsx`
- **Pattern Note:**
  - Demonstrates a rich modal with:
    - Custom Header with background image (lines 125-168)
    - Close button top-right
    - Scrollable content area
    - Two-column grid layout (lines 172-292)
  - **Reuse Strategy:** Copy the layout structure (Header + Content Grid) for the Testimonial Modal, but replace content with Alumni details.

## 2. Carousel / Slider Patterns

### A. Swiper (Recommended for Interactive/Touch)
**File:** `resources/js/Pages/AlumniPage.jsx`
- **Library:** `swiper/react`
- **Modules Used:** `Navigation`, `Pagination`
- **Implementation:**
  ```jsx
  <Swiper
      modules={[Navigation, Pagination]}
      spaceBetween={30}
      slidesPerView={1}
      navigation={{
          prevEl: '.custom-prev',
          nextEl: '.custom-next',
      }}
      pagination={{ clickable: true }}
      loop={true}
  >
      {slides.map(slide => <SwiperSlide>...</SwiperSlide>)}
  </Swiper>
  ```
- **Styling:** Custom navigation buttons positioned absolutely (lines 327-338).
- **Fit for Task:** Perfect for the "multi-image display" requirement inside the modal.

### B. Custom React State Carousel (Simple)
**File:** `resources/js/Pages/BeritaDetailPage.jsx` (lines 196-255)
- **Implementation:**
  - Uses `useState` for `currentImageIndex`
  - Simple Next/Prev functions
  - Thumbnails strip below main image
- **Fit for Task:** Good if `swiper` feels too heavy for a simple image gallery, but `Swiper` is already installed and more robust for touch/swipe.

### C. CSS Animation (Infinite Scroll)
**File:** `resources/js/Pages/LandingPage.jsx` (lines 428-439)
- **Implementation:** CSS `@keyframes` for continuous scrolling.
- **Fit for Task:** Not suitable for interactive image browsing (viewing specific details).

## Integration Plan
1. **Modal:** Import `Modal` from `@/Components/Modal`.
2. **Gallery:** Use `Swiper` inside the modal for the multi-image requirement (already used in `AlumniPage`).
3. **Layout:** Adapt the `ProgramStudiModal` header design for the Alumni "Hero" feel.

### Example Code Snippet
```jsx
import Modal from '@/Components/Modal';
import { Swiper, SwiperSlide } from 'swiper/react';
import { Navigation, Pagination } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import 'swiper/css/pagination';

export default function TestimonialModal({ show, onClose, alumni }) {
    return (
        <Modal show={show} onClose={onClose} maxWidth="4xl">
             <div className="relative h-96">
                <Swiper modules={[Navigation, Pagination]} navigation pagination={{ clickable: true }} className="h-full">
                    {alumni.images.map(img => (
                        <SwiperSlide key={img}><img src={img} className="w-full h-full object-cover" /></SwiperSlide>
                    ))}
                </Swiper>
                <button onClick={onClose} className="absolute top-4 right-4 z-10 bg-white/20 p-2 rounded-full">Close</button>
             </div>
             <div className="p-6 text-center">
                <h3>{alumni.name}</h3>
                <p>{alumni.testimonial}</p>
             </div>
        </Modal>
    );
}
```
