<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\MarketingCampaign;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures for MarketingCampaign entity.
 *
 * These fixtures demonstrate various UTM parameter combinations:
 * - Different sources (google, facebook, newsletter, etc.)
 * - Different mediums (cpc, email, social, etc.)
 * - Campaign names with various formats
 * - Search terms for paid campaigns
 * - Content identifiers
 *
 * @author Héctor Franco Aceituno <hectorfranco@nowo.tech>
 * @copyright 2025 Nowo.tech
 */
class MarketingCampaignFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Example 1: Google Ads campaign
        $campaign1 = new MarketingCampaign();
        $campaign1->setUtmSource('google');
        $campaign1->setUtmMedium('cpc');
        $campaign1->setUtmCampaign('spring_sale_2024');
        $campaign1->setUtmTerm('running shoes');
        $campaign1->setUtmContent('ad_variant_a');
        $campaign1->setUtmSourceKebab('google');
        $campaign1->setUtmSourceCustom('partner_a');
        $campaign1->setCreatedAt(new \DateTimeImmutable('2024-03-15 10:00:00'));
        $manager->persist($campaign1);

        // Example 2: Facebook social campaign
        $campaign2 = new MarketingCampaign();
        $campaign2->setUtmSource('facebook');
        $campaign2->setUtmMedium('social');
        $campaign2->setUtmCampaign('product_launch');
        $campaign2->setUtmTerm(null);
        $campaign2->setUtmContent('post_image_1');
        $campaign2->setUtmSourceKebab('facebook');
        $campaign2->setUtmSourceCustom('partner_b');
        $campaign2->setCreatedAt(new \DateTimeImmutable('2024-03-20 14:30:00'));
        $manager->persist($campaign2);

        // Example 3: Email newsletter campaign
        $campaign3 = new MarketingCampaign();
        $campaign3->setUtmSource('newsletter');
        $campaign3->setUtmMedium('email');
        $campaign3->setUtmCampaign('weekly_digest');
        $campaign3->setUtmTerm(null);
        $campaign3->setUtmContent('link_top');
        $campaign3->setUtmSourceKebab('newsletter');
        $campaign3->setUtmSourceCustom('partner_c');
        $campaign3->setCreatedAt(new \DateTimeImmutable('2024-03-25 09:15:00'));
        $manager->persist($campaign3);

        // Example 4: LinkedIn B2B campaign
        $campaign4 = new MarketingCampaign();
        $campaign4->setUtmSource('linkedin');
        $campaign4->setUtmMedium('cpc');
        $campaign4->setUtmCampaign('b2b_webinar');
        $campaign4->setUtmTerm('enterprise software');
        $campaign4->setUtmContent('sponsored_post');
        $campaign4->setUtmSourceKebab('linkedin');
        $campaign4->setUtmSourceCustom('partner_a');
        $campaign4->setCreatedAt(new \DateTimeImmutable('2024-04-01 11:00:00'));
        $manager->persist($campaign4);

        // Example 5: Instagram influencer campaign
        $campaign5 = new MarketingCampaign();
        $campaign5->setUtmSource('instagram');
        $campaign5->setUtmMedium('social');
        $campaign5->setUtmCampaign('influencer_collab');
        $campaign5->setUtmTerm(null);
        $campaign5->setUtmContent('story_link');
        $campaign5->setUtmSourceKebab('instagram');
        $campaign5->setUtmSourceCustom('partner_b');
        $campaign5->setCreatedAt(new \DateTimeImmutable('2024-04-05 16:45:00'));
        $manager->persist($campaign5);

        // Example 6: YouTube video campaign
        $campaign6 = new MarketingCampaign();
        $campaign6->setUtmSource('youtube');
        $campaign6->setUtmMedium('video');
        $campaign6->setUtmCampaign('tutorial_series');
        $campaign6->setUtmTerm(null);
        $campaign6->setUtmContent('video_description_link');
        $campaign6->setUtmSourceKebab('youtube');
        $campaign6->setUtmSourceCustom('partner_c');
        $campaign6->setCreatedAt(new \DateTimeImmutable('2024-04-10 13:20:00'));
        $manager->persist($campaign6);

        // Example 7: Direct referral
        $campaign7 = new MarketingCampaign();
        $campaign7->setUtmSource('referral');
        $campaign7->setUtmMedium('referral');
        $campaign7->setUtmCampaign('partner_referral');
        $campaign7->setUtmTerm(null);
        $campaign7->setUtmContent('referral_link_1');
        $campaign7->setUtmSourceKebab('referral');
        $campaign7->setUtmSourceCustom('partner_a');
        $campaign7->setCreatedAt(new \DateTimeImmutable('2024-04-15 10:30:00'));
        $manager->persist($campaign7);

        // Example 8: Twitter/X campaign
        $campaign8 = new MarketingCampaign();
        $campaign8->setUtmSource('twitter');
        $campaign8->setUtmMedium('social');
        $campaign8->setUtmCampaign('brand_awareness');
        $campaign8->setUtmTerm(null);
        $campaign8->setUtmContent('tweet_link');
        $campaign8->setUtmSourceKebab('twitter');
        $campaign8->setUtmSourceCustom('partner_b');
        $campaign8->setCreatedAt(new \DateTimeImmutable('2024-04-20 12:00:00'));
        $manager->persist($campaign8);

        $manager->flush();
    }
}
