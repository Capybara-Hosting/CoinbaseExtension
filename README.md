# 🚀 Coinbase Commerce Extension for Paymenter

> **Professional Cryptocurrency Payment Gateway for Paymenter**

[![BuiltByBit](https://img.shields.io/badge/BuiltByBit-Showcase-blue?style=for-the-badge&logo=bitcoin)](https://builtbybit.com)
[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10+-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

---

## 📖 About the Project

This extension integrates **Coinbase Commerce** cryptocurrency payments into your Paymenter installation, providing a seamless and secure way to accept Bitcoin, Ethereum, USDC, and other major cryptocurrencies.

Built with modern PHP practices and following Paymenter's extension architecture, this extension offers **enterprise-grade security** and reliability for cryptocurrency transactions.

### ✨ Key Features

- ✅ **Accept payments in major cryptocurrencies** (Bitcoin, Ethereum, USDC, etc.)
- ✅ **Secure webhook handling** for payment status updates
- ✅ **Automatic invoice processing** with real-time updates
- ✅ **Professional payment interface** with modern UI/UX
- ✅ **Support for both test and production environments**
- ✅ **Intelligent charge reuse** to prevent duplicate charges
- ✅ **Comprehensive security features** and validation
- ✅ **Easy configuration** and setup process

---

## 🖼️ User Interface Screenshots

### Payment Method Selection
![Payment Method Selection](photos/ChoseHowToPay.png)
*Clean interface for customers to choose their preferred cryptocurrency payment method*

### Cryptocurrency Payment Interface
![Cryptocurrency Payment](photos/CryptoSelectorPayment.png)
*Professional payment interface with network selection and fee breakdown*

---

## 🛠️ Technical Specifications

### 🔄 Payment Processing
- Creates charges on Coinbase Commerce API
- Stores charge ID in invoice properties
- Handles multiple currencies seamlessly
- Intelligent charge reuse logic

### 🔗 Webhook Processing
- Receives and verifies webhook notifications
- Uses HMAC SHA256 signature verification
- Processes all major event types automatically:
  - `charge:created`
  - `charge:pending`
  - `charge:confirmed`
  - `charge:failed`

### 🛡️ Security Features
- Webhook signature verification
- CSRF protection (disabled only for webhook)
- Comprehensive logging and monitoring
- Error handling and validation
- Duplicate payment prevention

### ⚙️ Configuration
- API Key management
- Webhook Secret configuration
- Test Mode toggle
- Charge reuse window settings
- Proper field validation

---

## 🚀 Quick Installation Guide

### 1. **Extension Setup**
```bash
# Ensure the extension files are in the correct directory
extensions/Gateways/Coinbase-Commerce/
```

### 2. **Get Your API Credentials**
- **API Key**: Go to [Coinbase Commerce Settings > Security](https://beta.commerce.coinbase.com/settings/security)
- **Webhook Secret**: Go to [Settings > Notifications](https://beta.commerce.coinbase.com/settings/notifications)

### 3. **Configure in Paymenter Admin**
1. Navigate to **Admin > Extensions > Gateways**
2. Create new gateway or edit existing one
3. Select **Coinbase Commerce** as the gateway type
4. Fill in the configuration fields

### 4. **Set Up Webhook**
- **Webhook URL**: `https://yourdomain.com/extensions/coinbasecommerce/webhook`
- **Events to select**:
  - `charge:created`
  - `charge:pending`
  - `charge:confirmed`
  - `charge:failed`

---

## 🔧 Configuration Options

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `api_key` | Text | ✅ | Your Coinbase Commerce API key |
| `webhook_secret` | Text | ✅ | Your webhook shared secret |
| `test_mode` | Checkbox | ❌ | Enable test mode for development |
| `charge_reuse_hours` | Number | ❌ | Hours to wait before creating new charges (default: 1) |

---

## 📋 Prerequisites

- [x] A **Coinbase Commerce** account at [commerce.coinbase.com](https://beta.commerce.coinbase.com/)
- [x] An **EVM-compatible wallet address** for receiving crypto deposits
- [x] **API key** from Coinbase Commerce
- [x] **Webhook secret** for secure communication
- [x] **HTTPS-enabled** domain (required for webhooks)

---

## 🔄 How It Works

### Payment Flow
1. **Customer** selects Coinbase Commerce as payment method
2. **Extension** checks for existing charges within configured time window
3. **If valid charge exists**: Reuses existing payment URL
4. **If no valid charge**: Creates new charge on Coinbase Commerce
5. **Customer** redirected to Coinbase's hosted payment page
6. **Customer** completes payment with preferred cryptocurrency
7. **Coinbase** sends webhook notifications for status updates
8. **Extension** processes webhooks and updates invoice status

### Charge Reuse Logic
The extension intelligently reuses existing charges to prevent duplicate charge creation:
- **Time Window**: Configurable hours (default: 1 hour)
- **Validation**: Checks charge status, expiration, and amount match
- **Benefits**: Reduces API calls, prevents duplicate charges, improves UX

---

## 🧪 Testing

### Test Mode Setup
1. Enable **Test Mode** in the configuration
2. Use Coinbase Commerce test environment
3. Test with small amounts first
4. Monitor logs for any errors

### Testing Checklist
- [ ] Payment creation works
- [ ] Webhook events are received
- [ ] Invoice status updates correctly
- [ ] Error handling works as expected
- [ ] Security features are functioning

---

## 🐛 Troubleshooting

### Common Issues

#### Webhook Not Receiving Events
- ✅ Verify webhook URL is accessible
- ✅ Check webhook secret is correct
- ✅ Ensure HTTPS is enabled
- ✅ Verify webhook endpoint is configured

#### Payment Not Processing
- ✅ Check API key is valid
- ✅ Verify webhook signature verification
- ✅ Review application logs
- ✅ Check Coinbase Commerce API status

#### Invoice Not Updating
- ✅ Confirm webhook events are being received
- ✅ Check invoice properties for charge ID
- ✅ Verify webhook processing logic
- ✅ Review error logs

### Logs
The extension logs all activities. Check your Laravel logs for:
- Payment creation attempts
- Webhook reception and processing
- Error messages and stack traces

---

## 📚 Support & Resources

### Extension Support
For issues with this extension:
1. Check the logs for error messages
2. Verify your Coinbase Commerce configuration
3. Ensure webhook endpoints are properly configured

### Coinbase Commerce Support
- **Help Center**: [Coinbase Commerce Help](https://help.coinbase.com/en/commerce)
- **API Documentation**: [Commerce API Docs](https://docs.commerce.coinbase.com/)
- **Community**: [Coinbase Community](https://community.coinbase.com/)

---

## 📝 Changelog

### Version 1.0.0
- 🎉 Initial release
- 💳 Basic payment processing
- 🔗 Webhook handling
- 🎨 Payment interface
- 🛡️ Security features

---

## 🤝 Contributing

We welcome contributions! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup
1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This extension is part of the Paymenter ecosystem and follows the same licensing terms.

---

## 👨‍💻 Author

**Dankata Pich**
- 🌐 Website: [https://dankata.eu.org](https://dankata.eu.org)
- 🚀 Built with ❤️ for the Paymenter community

---

## ⭐ Show Your Support

If you find this extension helpful, please consider:
- ⭐ **Starring** this repository
- 🔗 **Sharing** with your network
- 💬 **Providing feedback** and suggestions
- 🐛 **Reporting issues** you encounter
