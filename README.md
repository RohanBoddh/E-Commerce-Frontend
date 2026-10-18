# E-Commerce Frontend 🛒

A modern, high-converting college e-commerce storefront inspired by **Flipkart** and **Amazon**, tailored for campus deliveries.

## 🚀 Key Features

* **Flipkart & Amazon Inspired UI**:
  * Flipkart Signature Blue (`#2874f0`), Yellow (`#ff9f00`), and Orange (`#fb641b`) theme.
  * Google Fonts typography (**Plus Jakarta Sans** & **Inter**).
  * Category navigation strip with campus icons (Academics, Electronics, Hostel, Fashion, Sports, Snacks).
  * "Plus Assured" quality badges, star rating pills (`★ 4.3`), and discount tags (`XX% OFF`).
* **Rich Product Catalog**:
  * 49+ student products across 7 categories.
  * Search, category filtering, and sorting (Price Low to High, High to Low, Newest First, Name A-Z).
  * 2-column product detail page with image gallery, quantity stepper, stock status, and instant "BUY NOW" action.
* **Modern Payment & Checkout**:
  * **UPI & Dynamic QR Code**: Pay via Google Pay, PhonePe, Paytm, or BHIM.
  * **Credit / Debit Cards**: Formatted card inputs with card-type detection (Visa, Mastercard, RuPay).
  * **Net Banking**: Quick selection of top Indian banks (SBI, HDFC, ICICI, Axis, PNB, Kotak).
  * **Cash on Delivery (COD)**: Pay upon delivery to your campus hostel/department.
  * **Razorpay Gateway**: Live payment modal support.
* **Order Tracking & Admin Portal**:
  * Comprehensive order history with product breakdowns, payment status pills, unique transaction references, and printable invoices.
  * Secure administrative dashboard for managing products, categories, orders, and sales metrics.

## 💻 Tech Stack

* **Frontend**: HTML5, PHP 8.2, Vanilla CSS (Design Tokens & Flipkart System), JavaScript, Bootstrap 5.3, Bootstrap Icons.
* **Backend Integration**: MongoDB Atlas with transaction safety and stock management.

## 🌐 Live Production Deployment

* **Live Storefront URL**: **[https://college-e-commerce-five.vercel.app](https://college-e-commerce-five.vercel.app)**
* **Platform**: Vercel Serverless (Global Edge Network)
* **Backend Database**: MongoDB Atlas

## 🛠️ Local Development (Optional)

1. Ensure PHP 8.2+ is installed with `curl`, `openssl`, and `mongodb` extensions.
2. Start the local server:
   ```bash
   php -S 127.0.0.1:8000 -t .
   ```
3. Or visit the production live site at `https://college-e-commerce-five.vercel.app`.
