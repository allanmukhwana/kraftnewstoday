<?php
/**
 * Kraft News Today - Amazon Bedrock AI Agent Integration
 * Handles AI-powered article analysis using Amazon Bedrock (Vanilla PHP)
 */

require_once 'config.php';
require_once 'lib_bedrock_client.php';

class BedrockAgent {
    private $bedrock_client;
    
    public function __construct() {
        $this->bedrock_client = get_bedrock_client();
    }
    
    /**
     * Analyze article relevance
     * Determines if an article is relevant to the specified industry
     */
    public function analyzeRelevance($article_title, $article_content, $industry) {
        $prompt = "You are an AI agent analyzing news articles for relevance to specific industries.\n\n";
        $prompt .= "Industry: " . $industry . "\n";
        $prompt .= "Article Title: " . $article_title . "\n";
        $prompt .= "Article Content: " . substr($article_content, 0, 2000) . "\n\n";
        $prompt .= "Task: Determine if this article is relevant to the " . $industry . " industry.\n";
        $prompt .= "Provide a relevance score from 0.0 to 1.0 and a brief explanation.\n";
        $prompt .= "Response format: {\"score\": 0.85, \"explanation\": \"Brief explanation here\"}";
        
        $response = $this->invokeModel($prompt);
        
        if ($response) {
            // Parse JSON response
            $result = json_decode($response, true);
            if ($result && isset($result['score'])) {
                return $result;
            }
        }
        
        // Fallback response
        return ['score' => 0.5, 'explanation' => 'Unable to determine relevance'];
    }
    
    /**
     * Perform multi-dimensional analysis
     * Analyzes article across 7 dimensions
     */
    public function analyzeArticle($article_title, $article_content, $industry) {
        $dimensions = $GLOBALS['ANALYSIS_DIMENSIONS'];
        $analyses = [];
        
        foreach ($dimensions as $dimension_code => $dimension_name) {
            $analysis = $this->analyzeDimension($article_title, $article_content, $industry, $dimension_code, $dimension_name);
            $analyses[$dimension_code] = $analysis;
            
            // Add small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }
        
        return $analyses;
    }
    
    /**
     * Analyze specific dimension
     */
    private function analyzeDimension($title, $content, $industry, $dimension_code, $dimension_name) {
        $prompts = [
            'accuracy' => "Evaluate the accuracy and credibility of this article. Assess source reliability, fact-checking, and potential misinformation. Provide a score (0-1) and detailed analysis.",
            'impact' => "Analyze the potential impact of this news on the {$industry} industry. Consider market effects, stakeholder implications, and business consequences. Provide a score (0-1) and detailed analysis.",
            'trends' => "Identify trends and patterns in this article. What emerging trends does it reveal? How does it fit into broader industry movements? Provide insights and a significance score (0-1).",
            'strategic' => "Provide strategic implications and recommendations based on this article. What actions should industry professionals consider? What opportunities or threats exist? Include actionable insights.",
            'technical' => "Evaluate the technical accuracy and depth of this article. Assess the quality of technical explanations and identify any technical errors or oversimplifications. Provide a score (0-1) and analysis.",
            'competitive' => "Extract competitive intelligence from this article. Identify key players, competitive dynamics, market positioning, and strategic moves. Provide actionable competitive insights.",
            'bias' => "Detect bias and framing in this article. Identify political, commercial, or ideological bias. Analyze how the story is framed and what perspectives might be missing. Provide a bias score (0-1, where 0 is unbiased)."
        ];
        
        $prompt = "You are an expert analyst specializing in {$dimension_name} for the {$industry} industry.\n\n";
        $prompt .= "Article Title: {$title}\n";
        $prompt .= "Article Content: " . substr($content, 0, 3000) . "\n\n";
        $prompt .= "Task: {$prompts[$dimension_code]}\n\n";
        $prompt .= "Provide your analysis in the following JSON format:\n";
        $prompt .= "{\"score\": 0.85, \"analysis\": \"Detailed analysis here (2-3 paragraphs)\", \"key_points\": [\"Point 1\", \"Point 2\", \"Point 3\"]}";
        
        $response = $this->invokeModel($prompt);
        
        if ($response) {
            $result = json_decode($response, true);
            if ($result && isset($result['analysis'])) {
                return $result;
            }
        }
        
        // Fallback response
        return [
            'score' => 0.5,
            'analysis' => 'Analysis unavailable at this time.',
            'key_points' => []
        ];
    }
    
    /**
     * Generate article summary
     */
    public function generateSummary($article_title, $article_content) {
        $prompt = "Summarize the following article in 2-3 concise sentences. Focus on the most important information.\n\n";
        $prompt .= "Title: {$article_title}\n";
        $prompt .= "Content: " . substr($article_content, 0, 3000) . "\n\n";
        $prompt .= "Summary:";
        
        $response = $this->invokeModel($prompt, 200);
        return $response ?: 'Summary unavailable.';
    }
    
    /**
     * Invoke Amazon Bedrock model using vanilla PHP client
     */
    private function invokeModel($prompt, $max_tokens = BEDROCK_MAX_TOKENS) {
        // Check if API key is configured
        if (empty(BEDROCK_API_KEY)) {
            log_error("Bedrock API key not configured, using mock response");
            return $this->generateMockResponse($prompt);
        }
        
        // Try to call real Bedrock API
        $response = $this->bedrock_client->invokeModel($prompt, $max_tokens);
        
        // If API call fails, return mock response for demo
        if ($response === null) {
            log_error("Bedrock API call failed (check API key validity and model ID), using mock response");
            log_error("Current model: " . BEDROCK_MODEL_ID);
            return $this->generateMockResponse($prompt);
        }
        
        return $response;
    }
    
    /**
     * Generate mock response for demo/testing
     */
    private function generateMockResponse($prompt) {
        // Detect what type of analysis is being requested
        if (strpos($prompt, 'relevance score') !== false) {
            return json_encode([
                'score' => 0.85,
                'explanation' => 'This article is highly relevant to the industry, covering recent developments and key trends.'
            ]);
        }
        
        if (strpos($prompt, 'accuracy') !== false || strpos($prompt, 'Accuracy') !== false) {
            return json_encode([
                'score' => 0.88,
                'analysis' => 'The article demonstrates strong factual accuracy with credible sources cited throughout. The information appears well-researched and cross-referenced with multiple industry reports. Minor concerns exist regarding the lack of counterarguments to the main thesis presented.',
                'key_points' => [
                    'Multiple credible sources cited',
                    'Facts align with industry reports',
                    'Limited presentation of alternative viewpoints'
                ]
            ]);
        }
        
        if (strpos($prompt, 'impact') !== false || strpos($prompt, 'Impact') !== false) {
            return json_encode([
                'score' => 0.75,
                'analysis' => 'This development has significant implications for industry stakeholders. Market leaders will need to adapt their strategies to remain competitive. Small to medium enterprises may face challenges in implementation but could benefit from early adoption. The timeline for industry-wide impact is estimated at 12-18 months.',
                'key_points' => [
                    'Significant strategic implications for market leaders',
                    'Implementation challenges for SMEs',
                    'Expected industry transformation within 18 months'
                ]
            ]);
        }
        
        if (strpos($prompt, 'trends') !== false || strpos($prompt, 'Trend') !== false) {
            return json_encode([
                'score' => 0.82,
                'analysis' => 'The article highlights an accelerating trend toward digital transformation and automation. This aligns with broader industry movements observed over the past 24 months. Key indicators suggest this trend will intensify, driven by competitive pressures and technological advancement. Early adopters are already seeing measurable benefits.',
                'key_points' => [
                    'Acceleration of digital transformation initiatives',
                    'Alignment with 24-month industry trajectory',
                    'Competitive advantage for early adopters'
                ]
            ]);
        }
        
        if (strpos($prompt, 'strategic') !== false || strpos($prompt, 'Strategic') !== false) {
            return json_encode([
                'score' => 0.79,
                'analysis' => 'Organizations should prioritize investment in related capabilities within the next 6-12 months. Key strategic recommendations include: forming cross-functional teams to evaluate implementation, conducting pilot programs before full deployment, and monitoring competitor responses. Risk mitigation strategies should address potential regulatory changes and market volatility.',
                'key_points' => [
                    'Invest in capability development within 6-12 months',
                    'Implement pilot programs before scaling',
                    'Monitor regulatory landscape and competitor actions'
                ]
            ]);
        }
        
        if (strpos($prompt, 'technical') !== false || strpos($prompt, 'Technical') !== false) {
            return json_encode([
                'score' => 0.71,
                'analysis' => 'The technical content is generally accurate but somewhat simplified for a general audience. Core concepts are explained clearly, though some nuances are glossed over. Technical professionals may find the depth insufficient, while the explanations are appropriate for business decision-makers. No significant technical errors were identified.',
                'key_points' => [
                    'Accurate but simplified technical explanations',
                    'Appropriate for business audience',
                    'Lacks depth for technical specialists'
                ]
            ]);
        }
        
        if (strpos($prompt, 'competitive') !== false || strpos($prompt, 'Competitive') !== false) {
            return json_encode([
                'score' => 0.84,
                'analysis' => 'The article reveals important competitive dynamics with three major players making strategic moves. Market leader positioning appears vulnerable to disruption from emerging competitors with innovative approaches. Partnership opportunities exist for companies willing to collaborate rather than compete directly. Pricing pressures are likely to intensify in the next quarter.',
                'key_points' => [
                    'Three major players making strategic moves',
                    'Market leader vulnerable to disruption',
                    'Partnership opportunities emerging'
                ]
            ]);
        }
        
        if (strpos($prompt, 'bias') !== false || strpos($prompt, 'Bias') !== false) {
            return json_encode([
                'score' => 0.35,
                'analysis' => 'The article shows moderate bias favoring established industry players and traditional approaches. Language choices subtly frame new entrants as risky while established solutions are portrayed as safe. Missing perspectives include voices from smaller companies and international markets. The framing emphasizes potential problems over opportunities, suggesting a conservative editorial stance.',
                'key_points' => [
                    'Moderate bias toward established players',
                    'Risk-focused framing of new approaches',
                    'Limited representation of diverse perspectives'
                ]
            ]);
        }
        
        // Default summary response
        return 'This article provides important insights into recent industry developments, highlighting key trends and their potential impact on stakeholders. The analysis suggests strategic implications that organizations should consider in their planning processes.';
    }
}

// Helper function to get Bedrock agent instance
function get_bedrock_agent() {
    static $agent = null;
    if ($agent === null) {
        $agent = new BedrockAgent();
    }
    return $agent;
}
