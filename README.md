# Kraft News Today - AWS AI Agent Hackathon Submission

## Elevator Pitch
**An AI Agent that autonomously discovers, analyzes, and delivers personalized industry news insights—helping professionals stay ahead in their craft with intelligent analysis.**

---

## Inspiration

In today's fast-paced world, professionals struggle to keep up with industry developments. We're drowning in information but starving for insights. Reading dozens of articles daily isn't sustainable, and important developments often get missed or misunderstood.

We envisioned an AI Agent that doesn't just aggregate news—it thinks like an industry analyst. An autonomous agent that continuously monitors the web, evaluates relevance, performs deep analysis, and proactively delivers actionable intelligence. This hackathon's focus on building agents that reason, connect to external tools, and execute complex tasks aligned perfectly with our vision of creating a truly intelligent news analysis platform.

---

## What It Does

Kraft News Today is an autonomous AI Agent platform powered by Amazon Bedrock that transforms how professionals consume industry news:

**Core Capabilities:**

1. **Autonomous News Discovery**: The AI Agent continuously scrapes multiple web sources, intelligently filtering for relevance to user-selected industries

2. **Multi-Dimensional Analysis**: For each article, the Agent performs comprehensive analysis including:
   - Fact verification and source credibility assessment
   - Trend identification and pattern recognition
   - Stakeholder impact analysis
   - Technical accuracy evaluation
   - Bias and framing detection
   - Competitive intelligence extraction
   - Strategic implications synthesis

3. **Reasoning & Decision Making**: The Agent uses Amazon Bedrock's reasoning capabilities to:
   - Determine article relevance scores
   - Prioritize content based on impact
   - Synthesize insights across multiple sources
   - Generate actionable recommendations

4. **Automated Delivery**: Sends personalized digests twice daily (7 AM/7 PM) based on user timezone

5. **Multi-Industry Tracking**: Users can monitor multiple industries simultaneously, with the Agent adapting its analysis to each sector's unique context

---

## How We Built It

**AI Agent Architecture (Amazon Bedrock):**
- Implemented autonomous agent using Amazon Bedrock's Claude models for natural language understanding and generation
- Designed agent workflow with reasoning capabilities to evaluate news relevance and importance
- Created analysis pipelines that break down articles across 7+ analytical dimensions
- Built contextual understanding by maintaining industry-specific knowledge bases

**Backend Infrastructure:**
- PHP-based REST API for agent orchestration and task scheduling
- MySQL database for storing user preferences, scraped articles, and analysis results
- Cron jobs triggering the AI Agent at scheduled intervals
- Web scraping module that the Agent controls to fetch articles from multiple sources

**External Tool Integration:**
- Connected Agent to web scraping APIs for autonomous news discovery
- Integrated Brevo/Amazon SES for email delivery
- Stripe API for subscription management
- Timezone-aware scheduling system

**Frontend:**
- Responsive Bootstrap interface for user onboarding and preference management
- jQuery-powered dynamic content loading
- Clean, modern UI with accessibility in mind

**Agent Workflow:**
1. Agent receives trigger (scheduled or on-demand)
2. Agent queries external sources based on user industry preferences
3. Agent evaluates each article for relevance using reasoning
4. Agent performs multi-dimensional analysis on relevant articles
5. Agent synthesizes findings into digestible insights
6. Agent triggers email delivery with personalized content

---

## Challenges We Ran Into

**1. Agent Reasoning Optimization**
Getting the AI Agent to consistently make good decisions about article relevance was challenging. We had to fine-tune prompts and implement scoring mechanisms to help the Agent prioritize effectively.

**2. Analysis Consistency**
Ensuring the Agent performed comprehensive analysis across all seven dimensions without becoming verbose or repetitive required careful prompt engineering and output structuring.

**3. Web Scraping Reliability**
Different news sources have different structures. Teaching the Agent to adapt to various HTML structures and extract clean content was complex.

**4. Scalability Concerns**
Processing multiple articles with deep AI analysis for multiple users could become expensive. We implemented intelligent batching and caching strategies.

**5. Context Window Management**
Long articles sometimes exceeded token limits. We developed summarization pre-processing to ensure the Agent could analyze any article regardless of length.

**6. Timezone Coordination**
Sending emails at the right local time for users across different timezones while managing agent execution efficiently required careful architecture.

---

## Accomplishments That We're Proud Of

✅ **Built a True Autonomous Agent**: Not just an AI feature, but an agent that independently discovers, reasons, and acts

✅ **Multi-Dimensional Analysis**: Implemented seven different analytical lenses that provide genuine professional insights

✅ **End-to-End Automation**: From discovery to delivery, the entire pipeline runs autonomously

✅ **Scalable Architecture**: Designed to handle multiple industries and users efficiently

✅ **Production-Ready MVP**: Created a fully functional application ready for real-world use

✅ **Clean, Intuitive UX**: Built an interface that makes complex AI capabilities accessible

✅ **Intelligent Reasoning**: The Agent makes contextual decisions about what matters most

---

## What We Learned

**Technical Learnings:**
- How to architect autonomous agents that chain multiple reasoning steps
- Prompt engineering techniques for consistent, structured AI outputs
- Balancing AI capability with cost efficiency in production systems
- Managing context windows and token limits in complex workflows

**AI Agent Design:**
- Agents need clear decision-making frameworks to operate autonomously
- Breaking complex tasks into discrete agent capabilities improves reliability
- Human-in-the-loop isn't always necessary when agents have good guardrails
- Agent memory and context management is crucial for quality outputs

**Product Insights:**
- Professionals value insights over information volume
- AI analysis is most valuable when it's multi-dimensional
- Timing and personalization significantly impact engagement
- Trust is built through transparency in how the Agent works

---

## What's Next for Kraft News Today

**Short-term (3-6 months):**
- **Agent Memory**: Implement long-term memory so the Agent learns user preferences over time
- **Interactive Agent Chat**: Allow users to ask the Agent follow-up questions about analyzed articles
- **Source Diversification**: Expand the Agent's reach to industry journals, research papers, and podcasts
- **Sentiment Tracking**: Add Agent capability to track sentiment trends over time

**Medium-term (6-12 months):**
- **Multi-Agent Architecture**: Deploy specialized sub-agents for different industries with domain expertise
- **Predictive Insights**: Train the Agent to predict industry trends based on news patterns
- **Collaborative Filtering**: Enable the Agent to learn from what similar professionals find valuable
- **Custom Analysis Frameworks**: Let users define custom analytical dimensions for their specific needs

**Long-term Vision:**
- **Agentic Network**: Create an ecosystem where multiple specialized agents collaborate
- **Real-time Alerts**: Agent-triggered notifications for critical breaking news
- **Integration Marketplace**: Allow the Agent to connect with enterprise tools (Slack, Teams, CRM)
- **Voice Interface**: Natural language conversations with your news analysis agent
- **AI Research Assistant**: Evolve into a full research partner that can deep-dive on demand

---

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         USER LAYER                               │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐                  │
│  │ Web App  │    │ Mobile   │    │  Email   │                  │
│  │Bootstrap │    │Responsive│    │ Client   │                  │
│  │  jQuery  │    │   PWA    │    │          │                  │
│  └────┬─────┘    └────┬─────┘    └────▲─────┘                  │
└───────┼───────────────┼───────────────┼────────────────────────┘
        │               │               │
        └───────────────┴───────────────┘
                        │
        ┌───────────────▼────────────────┐
        │     PHP Backend (MVC)          │
        │  ┌──────────────────────┐      │
        │  │  User Management     │      │
        │  │  - auth.php          │      │
        │  │  - profile.php       │      │
        │  └──────────────────────┘      │
        │  ┌──────────────────────┐      │
        │  │  Industry Management │      │
        │  │  - industries.php    │      │
        │  └──────────────────────┘      │
        │  ┌──────────────────────┐      │
        │  │  Subscription Mgmt   │      │
        │  │  - payment.php       │◄─────┼───┐
        │  └──────────────────────┘      │   │
        └────────┬──────────┬─────────────┘   │
                 │          │                 │
        ┌────────▼──────────▼─────────────┐   │
        │      MySQL Database             │   │
        │  ┌────────────────────────┐     │   │
        │  │ users                  │     │   │
        │  │ user_industries        │     │   │
        │  │ articles               │     │   │
        │  │ article_analysis       │     │   │
        │  │ subscriptions          │     │   │
        │  │ agent_logs             │     │   │
        │  └────────────────────────┘     │   │
        └─────────────▲──────────────────┘   │
                      │                       │
        ┌─────────────┴──────────────────┐   │
        │    AI AGENT ORCHESTRATOR       │   │
        │    (agent_controller.php)      │   │
        │  ┌──────────────────────────┐  │   │
        │  │ Agent Scheduler          │  │   │
        │  │ - Cron Jobs (7AM/7PM)   │  │   │
        │  │ - Timezone Management   │  │   │
        │  └──────────┬───────────────┘  │   │
        │             │                   │   │
        │  ┌──────────▼───────────────┐  │   │
        │  │ Agent Task Manager       │  │   │
        │  │ - News Discovery         │  │   │
        │  │ - Relevance Scoring      │  │   │
        │  │ - Analysis Orchestration │  │   │
        │  └──────────┬───────────────┘  │   │
        └─────────────┼──────────────────┘   │
                      │                       │
        ┌─────────────┴──────────────────┐   │
        │   AMAZON BEDROCK AI AGENT      │   │
        │   ┌────────────────────────┐   │   │
        │   │ Claude Model           │   │   │
        │   │ (Reasoning & Analysis) │   │   │
        │   └──────┬─────────────────┘   │   │
        │          │                     │   │
        │   ┌──────▼─────────────────┐   │   │
        │   │ Agent Capabilities:    │   │   │
        │   │                        │   │   │
        │   │ 1. Content Evaluator  │   │   │
        │   │    - Relevance check  │   │   │
        │   │    - Quality scoring  │   │   │
        │   │                        │   │   │
        │   │ 2. Multi-Dimensional  │   │   │
        │   │    Analyzer           │   │   │
        │   │    - Accuracy check   │   │   │
        │   │    - Trend detection  │   │   │
        │   │    - Impact analysis  │   │   │
        │   │    - Bias detection   │   │   │
        │   │                        │   │   │
        │   │ 3. Insight Synthesizer│   │   │
        │   │    - Summary gen      │   │   │
        │   │    - Recommendations  │   │   │
        │   └────────────────────────┘   │   │
        └────────────────────────────────┘   │
                      │                       │
        ┌─────────────┴──────────────────┐   │
        │   EXTERNAL TOOLS & APIS        │   │
        │                                 │   │
        │  ┌──────────────────────────┐  │   │
        │  │ Web Scraping Module     │  │   │
        │  │ - News APIs             │  │   │
        │  │ - RSS Feeds             │  │   │
        │  │ - HTML Parsers          │  │   │
        │  └──────────────────────────┘  │   │
        │                                 │   │
        │  ┌──────────────────────────┐  │   │
        │  │ Email Service           │  │   │
        │  │ - Brevo API             │  │   │
        │  │ - Amazon SES            │  │   │
        │  │ - Template Engine       │  │   │
        │  └──────────────────────────┘  │   │
        │                                 │   │
        │  ┌──────────────────────────┐  │   │
        │  │ Stripe Payment API      │◄─┼───┘
        │  │ - Subscription Mgmt     │  │
        │  │ - Webhook Handler       │  │
        │  └──────────────────────────┘  │
        └─────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                      DATA FLOW                                   │
│                                                                   │
│  1. User Preferences → Database                                  │
│  2. Scheduler triggers Agent → Agent Controller                  │
│  3. Agent queries External Tools (Web Scraping)                  │
│  4. Raw articles → Amazon Bedrock Agent                          │
│  5. Bedrock analyzes with reasoning → Structured insights        │
│  6. Insights stored → Database                                   │
│  7. Agent triggers Email Service → User receives digest          │
│                                                                   │
└─────────────────────────────────────────────────────────────────┘
```

### Key Architecture Components

**1. User Layer**
- Progressive Web App (PWA) with Bootstrap & jQuery
- Mobile-first responsive design
- Email client for digest delivery

**2. Backend Layer (PHP)**
- MVC architecture with modular routing
- User authentication & authorization
- Industry preference management
- Subscription & payment processing

**3. Database Layer (MySQL)**
- User profiles & preferences
- Article storage & metadata
- AI analysis results
- Agent execution logs
- Subscription status

**4. AI Agent Orchestrator**
- Cron-based scheduling (7 AM/7 PM per timezone)
- Task queue management
- Agent workflow coordination
- Error handling & retry logic

**5. Amazon Bedrock AI Agent (Core)**
- **Reasoning Engine**: Evaluates article relevance and importance
- **Analysis Pipeline**: Performs 7-dimensional analysis
- **Synthesis Module**: Generates actionable insights
- Uses Claude model for natural language understanding

**6. External Tools Integration**
- Web scraping APIs for autonomous news discovery
- Email services (Brevo/SES) for delivery
- Stripe for payment processing

### Agent Workflow
```
Trigger → Discover → Reason → Analyze → Synthesize → Deliver
   ↓         ↓         ↓         ↓          ↓           ↓
Schedule  Scrape   Score    Bedrock   Format      Email
```
